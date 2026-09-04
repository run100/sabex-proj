<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoSite;
use App\Services\Seo\SabRenderService;
use App\Services\Seo\SabWikiAdminAbuseScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\TestCase;

class SabWikiAdminAbuseScheduleServiceTest extends TestCase
{
    use CreatesSabWikiTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeoTables();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_dry_run_parses_both_sources_without_writing_game_settings(): void
    {
        $game = $this->seedGame();
        Http::fake($this->sourceResponses(
            'Every Saturday, usually at 3PM EST. Admin Abuse lasts 30-45 minutes and may include Lucky Blocks.',
            'Every Tuesday at 6:00pm EST. Taco Tuesday lasts 30 to 60 minutes.',
        ));
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 12:00:00', 'America/New_York'));

        $result = app(SabWikiAdminAbuseScheduleService::class)->preview($game);

        $this->assertTrue($result['dry_run']);
        $this->assertTrue($result['changed']);
        $this->assertSame('Saturday', $result['data']['admin_abuse']['weekday']);
        $this->assertSame('15:00', $result['data']['admin_abuse']['time']);
        $this->assertSame('Tuesday', $result['data']['taco_tuesday']['weekday']);
        $this->assertSame('18:00', $result['data']['taco_tuesday']['time']);
        $this->assertSame([], $game->fresh()->settings_json);
    }

    public function test_manual_schedule_is_not_overwritten_and_past_next_date_is_recomputed(): void
    {
        $game = $this->seedGame([
            'sab_wiki' => [
                'admin_abuse' => [
                    'manual_override' => true,
                    'admin_abuse' => [
                        'weekday' => 'Saturday',
                        'time' => '14:00',
                        'next_event_at' => '2026-08-29T14:00:00-04:00',
                        'status' => 'confirmed',
                        'source' => 'manual',
                    ],
                ],
            ],
        ]);
        Http::fake($this->sourceResponses(
            'Every Saturday at 3PM EST. Admin Abuse lasts 30 minutes.',
            'Every Tuesday at 6PM EST.',
        ));
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 12:00:00', 'America/New_York'));

        app(SabWikiAdminAbuseScheduleService::class)->sync($game);
        $stored = $game->fresh()->settings_json;

        $this->assertSame('14:00', data_get($stored, 'sab_wiki.admin_abuse.admin_abuse.time'));
        $this->assertSame('manual', data_get($stored, 'sab_wiki.admin_abuse.confidence'));
        $this->assertSame('18:00', data_get($stored, 'sab_wiki.admin_abuse.taco_tuesday.time'));
        $page = app(SabWikiAdminAbuseScheduleService::class)->pageData($game->fresh(), CarbonImmutable::now('America/New_York'));
        $this->assertSame('2026-09-05', substr((string) data_get($page, 'admin_abuse.next_event_at'), 0, 10));
    }

    public function test_source_failure_keeps_last_successful_schedule(): void
    {
        $game = $this->seedGame([
            'sab_wiki' => [
                'admin_abuse' => [
                    'status' => 'confirmed',
                    'source_checked_at' => '2026-08-31T12:00:00+00:00',
                    'admin_abuse' => ['weekday' => 'Saturday', 'time' => '15:00', 'status' => 'confirmed'],
                    'taco_tuesday' => ['weekday' => 'Tuesday', 'time' => '18:00', 'status' => 'confirmed'],
                ],
            ],
        ]);
        Http::fake(fn () => Http::response([], 503));

        $result = app(SabWikiAdminAbuseScheduleService::class)->sync($game);

        $this->assertNotEmpty($result['errors']);
        $this->assertSame('2026-08-31T12:00:00+00:00', data_get($result['data'], 'source_checked_at'));
        $this->assertSame('15:00', data_get($game->fresh()->settings_json, 'sab_wiki.admin_abuse.admin_abuse.time'));
        $this->assertSame('18:00', data_get($game->fresh()->settings_json, 'sab_wiki.admin_abuse.taco_tuesday.time'));
    }

    public function test_page_data_reports_today_live_upcoming_and_not_confirmed_states(): void
    {
        $game = $this->seedGame([
            'sab_wiki' => [
                'admin_abuse' => [
                    'status' => 'confirmed',
                    'admin_abuse' => [
                        'weekday' => 'Saturday',
                        'time' => '15:00',
                        'duration_max_minutes' => 45,
                        'status' => 'confirmed',
                    ],
                    'taco_tuesday' => [
                        'weekday' => 'Tuesday',
                        'time' => '18:00',
                        'duration_max_minutes' => 60,
                        'status' => 'confirmed',
                    ],
                ],
            ],
        ]);
        $service = app(SabWikiAdminAbuseScheduleService::class);

        $today = $service->pageData($game, CarbonImmutable::parse('2026-09-01 17:00:00', 'America/New_York'));
        $this->assertSame('today', $today['status']);
        $this->assertSame('today', $today['taco_tuesday']['status']);

        $live = $service->pageData($game, CarbonImmutable::parse('2026-09-01 18:30:00', 'America/New_York'));
        $this->assertSame('live', $live['status']);
        $this->assertTrue($live['taco_tuesday']['is_live']);

        $upcoming = $service->pageData($game, CarbonImmutable::parse('2026-09-01 20:00:00', 'America/New_York'));
        $this->assertSame('upcoming', $upcoming['status']);
        $this->assertSame('2026-09-05', substr($upcoming['admin_abuse']['next_event_at'], 0, 10));

        $game->forceFill(['settings_json' => []])->save();
        $unknown = $service->pageData($game->fresh(), CarbonImmutable::parse('2026-09-01 20:00:00', 'America/New_York'));
        $this->assertSame('not_confirmed', $unknown['status']);
        $this->assertSame([], $unknown['timezone_rows']);
    }

    public function test_page_data_converts_next_occurrence_into_player_timezones(): void
    {
        $game = $this->seedGame([
            'sab_wiki' => [
                'admin_abuse' => [
                    'status' => 'confirmed',
                    'admin_abuse' => [
                        'weekday' => 'Saturday',
                        'time' => '15:00',
                        'duration_min_minutes' => 30,
                        'duration_max_minutes' => 45,
                        'status' => 'confirmed',
                    ],
                    'taco_tuesday' => [
                        'weekday' => 'Tuesday',
                        'time' => '18:00',
                        'duration_min_minutes' => 30,
                        'duration_max_minutes' => 60,
                        'status' => 'confirmed',
                    ],
                ],
            ],
        ]);

        $page = app(SabWikiAdminAbuseScheduleService::class)->pageData(
            $game,
            CarbonImmutable::parse('2026-09-01 12:00:00', 'America/New_York'),
        );
        $zones = collect($page['timezone_rows'])->keyBy('id');

        $this->assertSame('2026-09-05T12:00:00-07:00', $zones['pacific']['admin_abuse']['datetime']);
        $this->assertSame('2026-09-05T19:00:00+00:00', $zones['utc']['admin_abuse']['datetime']);
        $this->assertSame('2026-09-06T03:00:00+08:00', $zones['singapore']['admin_abuse']['datetime']);
        $this->assertSame('2026-09-01T15:00:00-07:00', $zones['pacific']['taco_tuesday']['datetime']);
        $this->assertCount(13, $page['timezone_rows']);
    }

    public function test_new_york_display_switches_from_edt_to_est_after_daylight_saving_time(): void
    {
        $game = $this->seedGame([
            'sab_wiki' => [
                'admin_abuse' => [
                    'status' => 'confirmed',
                    'admin_abuse' => [
                        'weekday' => 'Saturday',
                        'time' => '15:00',
                        'status' => 'confirmed',
                    ],
                ],
            ],
        ]);

        $page = app(SabWikiAdminAbuseScheduleService::class)->pageData(
            $game,
            CarbonImmutable::parse('2026-11-03 12:00:00', 'America/New_York'),
        );

        $this->assertSame('Nov 7, 2026 at 3:00 PM EST', $page['admin_abuse']['eastern_time']);
        $this->assertSame('EST', $page['admin_abuse']['timezone_label']);
    }

    /** @param array<string, mixed> $settings */
    private function seedGame(array $settings = []): SeoGame
    {
        $site = SeoSite::query()->updateOrCreate(
            ['slug' => SabRenderService::SITE_SLUG],
            [
                'name' => 'SAB Exist Count',
                'domain' => 'sabexistcount.com',
                'base_url' => 'https://sabexistcount.com',
                'output_path' => 'website/sab-exist-count',
                'settings_json' => SabRenderService::defaultSiteSettings(),
            ],
        );

        return SeoGame::query()->updateOrCreate(
            ['seo_site_id' => $site->id, 'slug' => 'steal-a-brainrot'],
            [
                'name' => 'Steal a Brainrot',
                'source_url' => '',
                'settings_json' => $settings,
            ],
        );
    }

    /** @return array<string, callable> */
    private function sourceResponses(string $admin, string $taco): array
    {
        return [
            'https://stealabrainrot.fandom.com/api.php*' => function ($request) use ($admin, $taco) {
                $page = (string) $request->data()['page'];

                return Http::response([
                    'parse' => [
                        'wikitext' => ['*' => $page === 'Admin Abuse' ? $admin : $taco],
                    ],
                ]);
            },
        ];
    }
}
