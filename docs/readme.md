# sabex（sabexistcount.com）

独立 Laravel 站，对应线上 **sabexistcount.com**。从 GEOFlow（`geo-ant-design-pro`）里 `SITE_SLUG = sab-exist-count` 的页面和数据拷出来，不是前后端分离，也不是 sabcalculator.com。

对照源站用 GEOFlow preview：`http://127.0.0.1:8083/seo/sab/preview/`（本机是 `php83 artisan serve --port=8083`，不是 OpenResty）。

## 是什么 / 不是什么

| | 说明 |
| --- | --- |
| 是 | sabexistcount 整站：exist count、value list、codes、计算器、新闻、商品页 |
| 不是 | sabcalculator.com 独立站（GEOFlow 的 `/seo/sabcalculator/preview/`） |
| 不是 | GEOFlow 管理端 SPA |
| 第一期不做 | 交易贴、Discord 登录、GAG2、Wiki 页、Vercel/Neon |

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

`fpm83` 已指向 `unix:/tmp/php83.socket`。测通后把 `.env` 的 `APP_URL` 改成 `http://127.0.0.1:7310`（现在还是当初 artisan 的 `18088`）。

不要占用 `8083`：OpenResty 的 `*:8083` 是旧站 lolga；`127.0.0.1:8083` 是 GEOFlow artisan。

## 程序结构

- 控制器：`app/Http/Controllers/Seo/SabPublicController.php`
- 渲染：`app/Services/Seo/SabRenderService.php`（`SITE_SLUG = sab-exist-count`）
- 站点上下文：`app/Services/Seo/SabSiteContext.php`
- 日更：`app/Services/Seo/SabRotCalculatorSyncService.php`、`SabPriceHistoryWriter.php`
- 模板：`resources/views/seo/sab/`（从 GEOFlow 拷来，不含后来的 Wiki blade）
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
| `/news`、`/news/{slug}` | 新闻 |
| `/products/{slug}` | 商品 |
| `/games`、`/games/{slug}` | games |
| `/about-us`、`/privacy-policy`、`/terms-of-service` | 法律页 |
| `/robots.txt`、`/sitemap.xml` | robots / sitemap |

没有 `/wiki`。GEOFlow 后来加的 Wiki（all-brainrots、rarity、rebirth-list、admin-abuse 等）第一期不搬。

## 后台

规划：Vue 3 + Element Plus，目录名 `console/`，路径 `/j8xq-4n2m-w9kp`（`CONSOLE_PATH`）。

**路由和页面都还没做**，打开即 404。`ADMIN_ALLOW_IPS` 已配（默认本机），中间件未写。

上线方向：IP 白名单（Cloudflare 认 `CF-Connecting-IP`）+ 登录 + 不收录。不要把后台路径写进 `robots.txt`。sitemap / 前台不链后台；未授权返回 **404**；仅已登录响应当下加 `X-Robots-Tag: noindex`。

## 数据

表结构对齐 GEOFlow 的 `seo_*`。导入脚本在源仓库：`geo-ant-design-pro/scripts/export-sab-to-mysql.php`。

已进过 MySQL 的大致包括：`seo_sites`、`seo_games`、`seo_items`、`seo_item_variants`、`seo_item_current_values`、`seo_news_articles`、`seo_value_sources`、aliases、translations。首次导入时**表里已有行就整表 skip**，所以商品 / 新闻相对 GEOFlow 可能过时。观测表 `seo_item_observations` 不从 Postgres 覆盖，由本站日更自己写。

观测 `id` **没有 AUTO_INCREMENT**，插入必须手动赋 `max(id)+1`，且不要走 fillable `create(['id' => ...])`，要用 `$model->id = ...; $model->save()`。NOT NULL 字符串列用 `''`，不要 null。

商品图：`public/uploads/images/sab` 链到 GEOFlow 同名目录。

## 价格曲线

不再用 3.1MB 单文件 `sab-price-history.json`，也不再扫 observations 全表画图。

- 文件：`storage/app/seo/sab-price-history/{slug}.json`
- 格式：`{"slug","variants":{"1001":[{"date","value"}]}}`
- 商品页展示截近 30 天；文件里的点不裁，留给以后 1Y
- 日更只写 **本站** 这些 JSON，不再写 GEOFlow

## 日更

命令：`php83 artisan seo:sab-calculator-refresh`

调度在 `bootstrap/app.php`，默认 `04:30`。环境变量：`SAB_CALCULATOR_SYNC_ENABLED`、`SAB_CALCULATOR_SYNC_AT`。

行为：打 rot.rocks，只更新已有 listed 商品的 `current_values`；12h 相同 hash 不重复插观测；按 slug 合并当天点，覆盖写本站 `{slug}.json`，不删历史点。

GEOFlow 的 `seo:sab-calculator-refresh`（preview → 有变化才 sync → render）**不再作为本站日更入口**。

## 和 GEOFlow 的关系

| | GEOFlow | sabex |
| --- | --- | --- |
| 角色 | 源站 / 对照 preview、部分采集仍在那边跑 | 线上 sabexistcount 的独立拷贝 |
| 库 | Postgres，禁止为调试改它 | MySQL `sabexistcount` |
| Preview | `http://127.0.0.1:8083/seo/sab/preview/` | 本机拟用 `http://127.0.0.1:7310/` |
| Wiki | 已有 12 个 URL | 没有 |
| 价格 JSON 日更 | 不再给本站写 | 只在本站跑 |

待做（只同步数据，不搬 Wiki / 不改 blade）：

1. export 改成按主键 `INSERT ... ON DUPLICATE KEY UPDATE`（items / variants / current_values / news / games.settings_json / sources / aliases）。禁止 TRUNCATE / DELETE 观测
2. 覆盖本站在用的 `codes.json`、`storage/app/seo/sab-i18n.json`、`sab-exist-count-gallery.json`、`sab-calculator-meta.json`
3. 不拷 Wiki 用的 `rebirths.json`，不重写 `sab-price-history/`
4. 新商品图补到本站（确认 symlink），不删旧图
5. 对照 8083 preview：旧商品 + 新商品 exist / 当前价、`/news` 条数、codes；30D 图仍读本站 JSON

## 硬约束

- 禁止 `migrate:fresh` / `migrate:refresh` / 未确认的 destructive SQL / 批量删数据
- 不要自动执行 migration
- 不要改 GEOFlow Postgres
- 不要 commit，除非明确要求
- 不编造未核实的 exist / 价格数字
