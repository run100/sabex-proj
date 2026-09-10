# sabex（sabexistcount.com）

独立 Laravel 站，对应线上 **sabexistcount.com**。从 GEOFlow（`geo-ant-design-pro`）里 `SITE_SLUG = sab-exist-count` 的页面和数据拷出来，不是前后端分离，也不是 sabcalculator.com。

对照源站用 GEOFlow preview：`http://127.0.0.1:8083/seo/sab/preview/`（本机是 `php83 artisan serve --port=8083`，不是 OpenResty）。

## 项目来由

原来 sabexistcount.com 不是单独仓库，而是挂在 GEOFlow 里的一个 SEO 站点：

- 数据在 Postgres `geo_ant_design_pro` 的 `seo_*` 表，和 MM2、GAG2 等站共用同一套库
- 页面是 Blade + `seo:sab-render`，生成静态 HTML 推到 `~/project-seo/seo-sabexistcount` 再上线
- 采集、调价、新闻 seeder、preview 都在 GEOFlow 后台和 artisan 命令里

这样有几个问题：和别的站绑在一起、静态生成链路重、本机 preview 还要走 GEOFlow。所以另开 **sabex-proj**：Laravel 13 + PHP 8.3 + 本机 MySQL `sabexistcount`，把 sabexistcount **整站**做成动态 Blade SSR。

第一期边界（当时就钉死的）：

- 只搬现有公开站 URL 和数字，禁止编造 exist / 价格
- **表不走 Laravel migrate**：按 GEOFlow 现库列对照，人手建 MySQL 表，再导入
- 第一期不导 GEOFlow 那 24 万行全量 `seo_item_observations`
- 不做：交易贴、Discord 登录、GAG2、**sabcalculator.com 独立站**、Vercel/Neon、再往 `project-seo` 推整站静态 HTML
- Wiki（`/wiki`）后来在 GEOFlow 加了，**本站已挂**（无 preview 前缀、无 `/{locale}/wiki`）
- 后台规划 Vue 3 + Element Plus（`console/`，路径 `/j8xq-4n2m-w9kp`），**还没做**

后来又定了：价格日更只在 sabex 跑，不再写 GEOFlow；价格图改成按商品一个 JSON，不再扫观测全表。GEOFlow 只当对照 preview 和「把最新业务数据导过来」的源。

## 是什么 / 不是什么

| | 说明 |
| --- | --- |
| 是 | sabexistcount 整站：exist count、value list、codes、计算器、新闻、商品页、Wiki |
| 不是 | sabcalculator.com 独立站（GEOFlow 的 `/seo/sabcalculator/preview/`） |
| 不是 | GEOFlow 管理端 SPA |
| 第一期不做 | 交易贴、Discord 登录、GAG2、Vercel/Neon |
| Wiki | 已挂 `/wiki` 及 14 个子页（对照 GEOFlow preview，公开链无前缀） |

GEOFlow 里 `http://127.0.0.1:8083/seo/sab/preview/steal-a-brainrot-trading-calculator` 就是 sabexistcount 的计算器页，对应本站 `/steal-a-brainrot-trading-calculator`。

## 技术栈

- Laravel **13.26**（`composer.lock`），PHP **8.3**
- 公开站：nginx / OpenResty → PHP-FPM → `public/index.php` → Blade SSR
- 库：本机 MySQL `sabexistcount`（`127.0.0.1` / `root`）。源站数据在 GEOFlow Postgres `geo_ant_design_pro`，**不要改那套库**
- 无独立前端、无给前台的 SPA API

## 本机入口

仓库里没有 nginx 配置。本机 OpenResty 在 `/opt/homebrew/etc/openresty/`，`nginx.conf` 只 `include conf.d/*conf;`。

建议 vhost：`/opt/homebrew/etc/openresty/conf.d/731-sabex.conf`

```nginx
server {
    listen       7310;
    server_name  localhost;
    root /Users/coolshell/projects/ai2024/sabex-proj/public;

    access_log  /Users/coolshell/Downloads/devops-logs/nginx/sabex.access.log main;
    error_log  /Users/coolshell/Downloads/devops-logs/nginx/sabex.error.log;

    index index.html index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php(/|$) {
        fastcgi_pass   fpm83;
        fastcgi_index  index.php;
        fastcgi_split_path_info ^((?U).+\.php)(/?.+)$;
        include        fastcgi_params;
        fastcgi_param  PATH_INFO          $fastcgi_path_info;
        fastcgi_param  PATH_TRANSLATED    $document_root$fastcgi_path_info;
        fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;
    }
}
```

`fpm83` 已指向 `unix:/tmp/php83.socket`。本机实际 vhost 听的是 **7510**（`731-sabex.conf`），7310 没起来时用 `http://127.0.0.1:7510/`。`.env` 的 `APP_URL` 仍是当初 artisan 的 `18088`。

不要占用 `8083`：OpenResty 的 `*:8083` 是旧站 lolga；`127.0.0.1:8083` 是 GEOFlow artisan。

## 程序结构

- 控制器：`app/Http/Controllers/Seo/SabPublicController.php`
- 渲染：`app/Services/Seo/SabRenderService.php`（`SITE_SLUG = sab-exist-count`）
- 站点上下文：`app/Services/Seo/SabSiteContext.php`
- 日更：`app/Services/Seo/SabRotCalculatorSyncService.php`、`SabPriceHistoryWriter.php`
- 模板：`resources/views/seo/sab/`（含 `wiki*.blade.php` 与 `partials/_wiki-*`）
- 配置：`config/sab.php`（`CONSOLE_PATH`、`ADMIN_ALLOW_IPS`）

代码里仍有 `local_preview_base_url = http://127.0.0.1:8083`，那是对照 GEOFlow preview 用的，不是本站对外地址。

## 前台路由

无 URL 前缀。多语言是 `/{locale}/...`（默认英文无前缀）。

| 路径 | 页 |
| --- | --- |
| `/` | 首页 |
| `/sab-exist-count-list` | exist count 列表 |
| `/exist-count-gallery` | gallery |
| `/sab-value-list` | value list |
| `/value-changes` | 价值变动 |
| `/steal-a-brainrot-codes` | codes |
| `/steal-a-brainrot-trading-calculator` | 计算器 |
| `/wiki` | Wiki hub |
| `/wiki/{slug}` | Wiki 子页（白名单 14 个 slug，见下） |
| `/news`、`/news/{slug}` | 新闻 |
| `/products/{slug}` | 商品 |
| `/games`、`/games/{slug}` | games |
| `/about-us`、`/privacy-policy`、`/terms-of-service` | 法律页 |
| `/robots.txt`、`/sitemap.xml` | robots / sitemap |

Wiki **已挂** `/wiki`（无 preview 前缀，也无 `/{locale}/wiki`）。对照：`http://127.0.0.1:8083/seo/sab/preview/wiki`。未在白名单的路径（如 `/wiki/events`）保持 404。

| GEOFlow preview | 本站 |
| --- | --- |
| `/seo/sab/preview/wiki` | `/wiki` |
| `/seo/sab/preview/wiki/all-brainrots` | `/wiki/all-brainrots` |
| `/wiki/all-{common,rare,epic,legendary,mythic}-brainrots`、`/wiki/all-brainrot-god`、`/wiki/all-secret-brainrots`、`/wiki/all-og-brainrots` | 同路径 |
| `/wiki/steal-a-brainrot-rebirth-list`、`/wiki/admin-abuse` | 同路径（写入 sitemap） |
| `/wiki/rituals`、`/wiki/all-lucky-blocks`、`/wiki/all-fusions` | 同路径（路由已挂，不进 sitemap） |

`SabRenderService` 只补了 wiki 方法与活 sitemap 的 Wiki loc；商品 30D 仍读本站 `{slug}.json`。`robots.txt` 仍是全站 Allow，不写后台路径。

## 后台

规划：Vue 3 + Element Plus，目录名 `console/`，路径 `/j8xq-4n2m-w9kp`（`CONSOLE_PATH`）。

**路由和页面都还没做**，打开即 404。`ADMIN_ALLOW_IPS` 已配（默认本机），中间件未写。

上线方向：IP 白名单（Cloudflare 认 `CF-Connecting-IP`）+ 登录 + 不收录。不要把后台路径写进 `robots.txt`。sitemap / 前台不链后台；未授权返回 **404**；仅已登录响应当下加 `X-Robots-Tag: noindex`。

## 表怎么来的

sabex 的 Laravel migration **只有** `users` / `cache` / `jobs`，没有 `seo_*`。这些表是按 GEOFlow Postgres `information_schema` 人手在 MySQL 建的，列名对齐，**禁止再 `migrate` / `migrate:fresh` 去建或重建**。

类型对照：`bool` → `TINYINT(1)`，`timestamp` → `TIMESTAMP`，`json` → `JSON`，`numeric` → `DECIMAL`，`int8` → `BIGINT`。MySQL `TEXT` 只有 64KB，新闻 `body_html`、商品 `rot_rocks_description_html/text` 必须用 `LONGTEXT`。

本库现有 10 张业务表（没有 `seo_sync_runs`，日更也不依赖它）：

最近一次 upsert 打印（postgres / mysql 相同）：

```
seo_sites 1
  └── seo_games 1
        └── seo_items 582            （listed 579）
              ├── seo_item_aliases 2
              ├── seo_item_translations 0
              └── seo_item_variants 8571
                    ├── seo_item_current_values 4938   （每个 variant × source 一行最新价）
                    └── seo_item_observations 12676    （未从 GEOFlow 再导；本站日更自写）
seo_value_sources 14          （整表拷，含 rot-rocks-calculator）
seo_news_articles 54          （published 新闻 50 + draft 1 + type=100 静态 3）
```

站点行：`seo_sites.id=1`，`slug=sab-exist-count`，`base_url=https://sabexistcount.com`。游戏行：`seo_games.id=1`，`slug=steal-a-brainrot`。

### 每张表干什么

| 表 | 作用 | 关键列 / 约束 |
| --- | --- | --- |
| `seo_sites` | 站点 | `slug` 唯一。本站只有 `sab-exist-count` |
| `seo_games` | 游戏 | `(seo_site_id, slug)` 唯一 |
| `seo_value_sources` | 价格/exist 来源 | `rot-rocks-calculator` 是计算器日更源 |
| `seo_items` | 商品 | 55 列。常用：`slug`、`name`、`rarity`、`is_listed`、`total_exists`、`local_image_url`、`attributes_json`、趋势字段。listed 579 |
| `seo_item_variants` | 默认 / mutation / trait | `(seo_item_id, variant_key)` 唯一。`variant_key=base` 是默认形态 |
| `seo_item_current_values` | 每个 variant+来源的**当前** exist/value | `(seo_item_variant_id, seo_value_source_id)` 唯一 |
| `seo_item_observations` | 采集快照（只追加） | `id` **没有 AUTO_INCREMENT** |
| `seo_news_articles` | 新闻 + legal 类静态文 | `type`：100 静态页、200 新闻 |
| `seo_item_aliases` | 跨来源别名 | 现 2 行 |
| `seo_item_translations` | 商品译文 | 现 0 行；前台多语言主要靠 `sab-i18n.json` |

插入观测：必须 `$model->id = max(id)+1; $model->save()`，不要 `create(['id' => ...])`（`id` 不在 fillable）。NOT NULL 字符串用 `''`，不要 null。本库 `max(id)` 曾到约 306557（id 从 GEOFlow 近期观测拷过来，不是从 1 连号）。

页面读价：当前价走 `seo_item_current_values`；30 天图**不扫**观测表，读 `storage/app/seo/sab-price-history/{slug}.json`。

## 数据怎么搞的

两套库，脚本只读源、只写目标：

| | 源 GEOFlow | 目标 sabex |
| --- | --- | --- |
| 引擎 | Postgres | MySQL |
| 库 | `geo_ant_design_pro` | `sabexistcount` |
| 账号 | `coolshell` / 空密码 | `root` / `admin` |
| 范围 | `seo_sites.slug = sab-exist-count` 及相关行；`seo_value_sources` 整表 | 保留原 `id` |

### 1. 首次导入（已做过）

脚本在源仓库：`geo-ant-design-pro/scripts/export-sab-to-mysql.php`。PDO 直连两边，不用 CSV（新闻 HTML 会被 CSV 弄坏）。

```bash
cd /Users/coolshell/projects/ai2024/geo-ant-design-pro
php83 scripts/export-sab-to-mysql.php --dry-run
php83 scripts/export-sab-to-mysql.php
```

行为：

- 只处理 `sab-exist-count` 的 site / game / items / variants / current_values / news / aliases / translations，以及全部 `seo_value_sources`
- 按主键 `INSERT ... ON DUPLICATE KEY UPDATE`（更新非 id 列）。不 DELETE、不 TRUNCATE
- **永不**导出或覆盖 `seo_item_observations`，也不写 `sab-price-history/`。`--with-observations` / `--recent-observations` 若仍在 argv 里会打印 skip
- 去掉首次导入的硬校验（563 / 8451 / 4809 / 52），结束时打印 postgres vs mysql 行数后正常退出

库外文件（导入时另拷或 symlink，不在 SQL 里）：

| 本站路径 | 用途 |
| --- | --- |
| `public/uploads/images/sab` → GEOFlow 同名目录 | 商品图 symlink，新图补文件、不删旧图 |
| `resources/seo/sab/codes.json`（及 `codes-i18n*.json`） | codes 页 |
| `storage/app/seo/sab-i18n.json` | 站点文案翻译 |
| `storage/app/seo/sab-exist-count-gallery.json` | gallery |
| `storage/app/calc/sab/meta.json` | 计算器 meta |
| `storage/app/calc/sab/catalog/` | 计算器 Catalog、value-list manifest、完整索引与 mutation 分片 |
| `storage/app/seo/sab-exist-count-list.json` | 独立 Exist Count 首页与列表数据 |
| `storage/app/seo/sab-price-history/{slug}.json` | 现 561 个文件（新商品可能还没有；商品页读不到也不 500） |
| `resources/seo/sab/rebirths.json` | Wiki rebirth 指南 |

不要从 MySQL 全表重造价格 JSON（早期观测只有 base，mutation 历史在文件里）。

### 2. 本站计算器日更（独立，不经过 GEOFlow）

```bash
cd /Users/coolshell/projects/ai2024/sabex-proj
php83 artisan seo:sab-calculator-refresh
```

`bootstrap/app.php` 默认每天 `04:30`。`SAB_CALCULATOR_SYNC_ENABLED` / `SAB_CALCULATOR_SYNC_AT`。本机页面：`http://www.sabex.lab:7510/steal-a-brainrot-trading-calculator`。

打 rot.rocks 的 brainrots / mutations / traits，在本站 upsert 商品、变体、`seo_item_current_values`；远程新 slug **直接建商品**（`is_listed=true`，`is_publish_html=false`）。12h 相同 hash 不重复插观测。按 slug 合并当天点到本站 `{slug}.json`（不删历史点）。重写 `storage/app/calc/sab/meta.json`，并生成版本化 Catalog（完整索引由浏览器加载，mutation 详情按分片加载）。计算器图已在 `public/uploads/images/sab/calculator/` 则复用（本机这个目录若仍是 GEOFlow symlink，只读不写）。缺图下载到本站 `public/uploads/images/sab-calculator/`。不写 GEOFlow，也不生成静态 HTML。

新闻 / wiki / exist count / codes 仍可走下面第 3 步，和这条命令无关。

如果只需要根据本站已有数据库重新生成计算器 Catalog，不访问 rot.rocks：

```bash
php83 artisan seo:sab-calculator-catalog
```

这个命令只读取本站商品、变体、当前值和 `storage/app/calc/sab/meta.json`，生成 Calculator manifest、完整物品索引、mutation 分片和 value-list。首页与 `/sab-exist-count-list` 的低频 Exist Count 数据由独立命令生成，不再写入 Calculator Catalog。

如果只需要更新首页和 Exist Count List：

```bash
php83 artisan seo:sab-exist-count-refresh
```

这个命令只读取本站 MySQL 和本地数据，不访问 rot.rocks，原子写入 `storage/app/seo/sab-exist-count-list.json`。页面通过 `/data/seo/sab-exist-count-list.json` 读取它。`seo:sab-calculator-refresh` 不会更新这份低频 Exist Count 数据。

### 3. 再把 GEOFlow 最新业务数据同步过来（已按 upsert 做）

export 已是按主键 upsert。需要时再跑同一脚本即可（先 `--dry-run` 看行数）：

- `seo_sites` / `seo_games`（含 `settings_json`）
- `seo_items` / `seo_item_variants` / `seo_item_current_values`
- `seo_news_articles` / `seo_value_sources` / aliases / translations
- **不要**导出或覆盖 `seo_item_observations`
- **不要**重写 `sab-price-history/`
- checksum 不同才覆盖 `codes.json`、`sab-i18n.json`、gallery；`rebirths.json` 已在本站。计算器 meta 和 Catalog 由本站日更写，不要用 GEOFlow 覆盖
- 商品图目录是指向 GEOFlow 同名目录的 symlink，新图自动可见，不删旧图

对照：`http://127.0.0.1:8083/seo/sab/preview/wiki` 与各子页；抽旧商品 + 新商品的 exist/当前价、`/news` 条数、codes；30D 图仍读本站 JSON。本机 7310 若没起来，用实际入口 `http://127.0.0.1:7510/`。

## 价格曲线

不再用 3.1MB 单文件 `sab-price-history.json`，也不再扫 observations 全表画图。

- 文件：`storage/app/seo/sab-price-history/{slug}.json`
- 格式：`{"slug","variants":{"1001":[{"date","value"}]}}`
- 商品页展示截近 30 天；文件里的点不裁，留给以后 1Y
- 日更只写 **本站** 这些 JSON，不再写 GEOFlow
- 历史点补全（无参全量、实时日志、分批 SQL）见 [sab-price-history-backfill.md](sab-price-history-backfill.md)

## 日更

命令：`php83 artisan seo:sab-calculator-refresh`

调度在 `bootstrap/app.php`，默认 `04:30`。环境变量：`SAB_CALCULATOR_SYNC_ENABLED`、`SAB_CALCULATOR_SYNC_AT`。

行为：打 rot.rocks，upsert 本站商品 / 变体 / 当前价 / traits；远程新品直接建（不发布 SEO 商品页）；12h 相同 hash 不重复插观测；按 slug 合并当天点到本站 `{slug}.json`；写 `storage/app/calc/sab/meta.json` 和版本化 Catalog。动态页只加载轻量页面上下文，浏览器通过公开 JSON 路由加载完整物品索引，不再 `seo:sab-render`。

GEOFlow 的同名命令（preview → 有变化才 sync → render）**不再作为本站日更入口**。

## 和 GEOFlow 的关系

| | GEOFlow | sabex |
| --- | --- | --- |
| 角色 | 对照 preview、新闻 / wiki / exist / codes 仍可能先在那边落库 | 线上 sabexistcount 的独立动态站；计算器日更自给自足 |
| 库 | Postgres，**禁止为调试改它** | MySQL `sabexistcount` |
| Preview | `http://127.0.0.1:8083/seo/sab/preview/` | 本机拟用 `http://127.0.0.1:7310/` |
| Wiki | `/seo/sab/preview/wiki` 及子页 | 已挂 `/wiki`（公开站 `index,follow`） |
| 价格日更 | 不再给本站写 | `seo:sab-calculator-refresh` 只写本站 |

日常数据流：

1. sabex 每天跑 `seo:sab-calculator-refresh`，自己打 rot.rocks 更新当前价、traits、新品、`{slug}.json` 和 Catalog
2. GEOFlow 继续采集 / 人工改新闻、wiki、exist count、codes
3. Exist Count 低频更新时运行 `seo:sab-exist-count-refresh`，写入独立 `storage/app/seo/sab-exist-count-list.json`
4. 需要时再跑 upsert 版 export，把新闻 / wiki / exist / codes 同步到 sabex（不要覆盖观测和价格 JSON；calculator-meta 以本站日更为准）

## 硬约束

- 禁止 `migrate:fresh` / `migrate:refresh` / 未确认的 destructive SQL / 批量删数据
- 不要自动执行 migration
- 不要改 GEOFlow Postgres
- 不要 commit，除非明确要求
- 不编造未核实的 exist / 价格数字
