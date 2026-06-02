<?php
/**
 * Plugin Name: 子比主题美化增强
 * Plugin URI: https://github.com/your-repo/zibe-enhancer
 * Description: 为子比主题提供全方位视觉美化，包含界面动画、色彩主题定制、字体美化、侧边栏毛玻璃效果等，附带可视化后台设置面板。
 * Version: 1.0.0
 * Author: 小染
 * Author URI: https://yoursite.com
 * License: GPL-2.0-or-later
 * Text Domain: zibe-enhancer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── 常量 ────────────────────────────────────────────────────────────────────
define( 'ZIBE_ENH_VER',  '1.0.0' );
define( 'ZIBE_ENH_DIR',  plugin_dir_path( __FILE__ ) );
define( 'ZIBE_ENH_URL',  plugin_dir_url( __FILE__ ) );
define( 'ZIBE_ENH_OPT',  'zibe_enhancer_options' );

// ─── 默认选项 ────────────────────────────────────────────────────────────────
function zibe_enh_defaults() {
    return [
        // 动画
        'enable_animations'      => '1',
        'animation_speed'        => 'normal',   // slow | normal | fast
        'enable_mouse_effect'    => '1',
        'enable_ripple'          => '1',

        // 色彩
        'enable_color_theme'     => '1',
        'primary_color'          => '#4e6ef2',
        'secondary_color'        => '#f5a623',
        'link_hover_color'       => '#ff6b6b',
        'card_bg_color'          => '#ffffff',
        'enable_gradient_bg'     => '0',
        'gradient_start'         => '#667eea',
        'gradient_end'           => '#764ba2',

        // 字体
        'enable_custom_font'     => '0',
        'font_family'            => 'system',   // system | misans | noto | alibaba | custom
        'custom_font_name'       => '',
        'custom_font_url'        => '',
        'font_size_base'         => '15',
        'line_height'            => '1.8',

        // 侧边栏
        'sidebar_glass'          => '1',
        'sidebar_blur'           => '10',
        'sidebar_opacity'        => '85',
        'sidebar_border_radius'  => '12',
        'card_hover_lift'        => '1',
        'card_border_radius'     => '12',

        // 其他
        'enable_scrollbar'       => '1',
        'scrollbar_color'        => '#4e6ef2',
        'enable_back_top'        => '1',
        'enable_loading_bar'     => '1',
        'custom_css'             => '',
    ];
}

function zibe_enh_get( $key = null ) {
    $opts = wp_parse_args(
        (array) get_option( ZIBE_ENH_OPT, [] ),
        zibe_enh_defaults()
    );
    return $key ? ( $opts[ $key ] ?? null ) : $opts;
}

// ─── 前台：注入样式与脚本 ─────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'zibe_enh_frontend_assets' );
function zibe_enh_frontend_assets() {
    $opts = zibe_enh_get();
    $ver  = ZIBE_ENH_VER . '_' . substr( md5( serialize( $opts ) ), 0, 6 );

    wp_enqueue_style(
        'zibe-enhancer',
        ZIBE_ENH_URL . 'assets/css/enhancer.css',
        [],
        $ver
    );

    if ( $opts['enable_custom_font'] === '1' && $opts['font_family'] !== 'system' ) {
        if ( $opts['font_family'] === 'custom' ) {
            if ( ! empty( $opts['custom_font_url'] ) ) {
                wp_enqueue_style( 'zibe-enh-font-custom', esc_url( $opts['custom_font_url'] ), [], ZIBE_ENH_VER );
            }
        } else {
            $font_urls = [
                'misans'   => 'https://cdn.jsdelivr.net/npm/misans-webfont@1.0.1/dist/Normal/MiSans-Normal.css',
                'noto'     => 'https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;700&display=swap',
                'alibaba'  => 'https://puui.qpic.cn/vpic_cover/j3155icib7y4ia7yqLXicIcK2HCa1lvXJuib4UE5vPOAFBtFdCkNXTwpA/0',
            ];
            if ( isset( $font_urls[ $opts['font_family'] ] ) ) {
                wp_enqueue_style( 'zibe-enh-font', $font_urls[ $opts['font_family'] ], [], null );
            }
        }
    }

    // 内联 CSS 变量
    $inline_css = zibe_enh_build_css_vars( $opts );
    wp_add_inline_style( 'zibe-enhancer', $inline_css );

    if ( $opts['enable_animations'] === '1' || $opts['enable_mouse_effect'] === '1' || $opts['enable_ripple'] === '1' ) {
        wp_enqueue_script(
            'zibe-enhancer',
            ZIBE_ENH_URL . 'assets/js/enhancer.js',
            [],
            $ver,
            true
        );
        wp_localize_script( 'zibe-enhancer', 'ZibeEnhConfig', [
            'animations'   => $opts['enable_animations'],
            'mouseEffect'  => $opts['enable_mouse_effect'],
            'ripple'       => $opts['enable_ripple'],
            'loadingBar'   => $opts['enable_loading_bar'],
        ] );
    }
}

/**
 * 根据选项生成 CSS 自定义属性
 */
function zibe_enh_build_css_vars( $opts ) {
    $speed_map = [ 'slow' => '0.6s', 'normal' => '0.35s', 'fast' => '0.18s' ];
    $speed     = $speed_map[ $opts['animation_speed'] ] ?? '0.35s';

    $font_map = [
        'misans'  => '"MiSans", "PingFang SC", sans-serif',
        'noto'    => '"Noto Sans SC", sans-serif',
        'alibaba' => '"Alibaba PuHuiTi", "PingFang SC", sans-serif',
        'system'  => '-apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif',
    ];

    if ( $opts['font_family'] === 'custom' && ! empty( $opts['custom_font_name'] ) ) {
        $font = '"' . esc_attr( $opts['custom_font_name'] ) . '", "PingFang SC", "Microsoft YaHei", sans-serif';
    } else {
        $font = $font_map[ $opts['font_family'] ] ?? $font_map['system'];
    }

    $css  = ":root {\n";

    // 色彩主题（受总开关控制）
    if ( $opts['enable_color_theme'] === '1' ) {
        $css .= "  --zibe-primary:        {$opts['primary_color']};\n";
        $css .= "  --zibe-secondary:      {$opts['secondary_color']};\n";
        $css .= "  --zibe-link-hover:     {$opts['link_hover_color']};\n";
        $css .= "  --zibe-card-bg:        {$opts['card_bg_color']};\n";
        if ( $opts['enable_gradient_bg'] === '1' ) {
            $css .= "  --zibe-grad-start:     {$opts['gradient_start']};\n";
            $css .= "  --zibe-grad-end:       {$opts['gradient_end']};\n";
        }
    }

    $css .= "  --zibe-anim-speed:     {$speed};\n";
    $css .= "  --zibe-font:           {$font};\n";
    $css .= "  --zibe-font-size:      {$opts['font_size_base']}px;\n";
    $css .= "  --zibe-line-height:    {$opts['line_height']};\n";
    $css .= "  --zibe-blur:           {$opts['sidebar_blur']}px;\n";
    $css .= "  --zibe-sidebar-alpha:  " . ( intval( $opts['sidebar_opacity'] ) / 100 ) . ";\n";
    $css .= "  --zibe-radius:         {$opts['sidebar_border_radius']}px;\n";
    $css .= "  --zibe-card-radius:    {$opts['card_border_radius']}px;\n";
    $css .= "  --zibe-scrollbar:      {$opts['scrollbar_color']};\n";

    if ( $opts['enable_gradient_bg'] === '1' ) {
        $css .= "  --zibe-grad-start:     {$opts['gradient_start']};\n";
        $css .= "  --zibe-grad-end:       {$opts['gradient_end']};\n";
    }
    $css .= "}\n";

    // 条件关闭
    if ( $opts['enable_animations'] !== '1' ) {
        $css .= ".zibe-anim, .zibe-fade-in, .zibe-slide-up { animation: none !important; transition: none !important; }\n";
    }
    if ( $opts['enable_scrollbar'] !== '1' ) {
        $css .= "::-webkit-scrollbar { display: none; }\n";
    }
    if ( $opts['enable_custom_font'] === '1' ) {
        $css .= "body, .zib-post-con, .zib-widget-title { font-family: var(--zibe-font) !important; font-size: var(--zibe-font-size) !important; line-height: var(--zibe-line-height) !important; }\n";
    }
    // 渐变背景（也受色彩总开关控制）
    if ( $opts['enable_color_theme'] === '1' && $opts['enable_gradient_bg'] === '1' ) {
        $css .= "body { background: linear-gradient(135deg, var(--zibe-grad-start), var(--zibe-grad-end)) fixed !important; }\n";
    }
    if ( ! empty( $opts['custom_css'] ) ) {
        $css .= "\n/* Custom CSS */\n" . wp_strip_all_tags( $opts['custom_css'], false ) . "\n";
    }

    return $css;
}

// ─── 后台菜单与设置页面 ────────────────────────────────────────────────────────
add_action( 'admin_menu', 'zibe_enh_admin_menu' );
function zibe_enh_admin_menu() {
    add_menu_page(
        '子比美化增强',
        '子比美化',
        'manage_options',
        'zibe-enhancer',
        'zibe_enh_settings_page',
        'dashicons-art',
        60
    );
}

add_action( 'admin_enqueue_scripts', 'zibe_enh_admin_assets' );
function zibe_enh_admin_assets( $hook ) {
    if ( $hook !== 'toplevel_page_zibe-enhancer' ) return;

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_style(
        'zibe-enh-admin',
        ZIBE_ENH_URL . 'assets/css/admin.css',
        [],
        ZIBE_ENH_VER
    );
    wp_enqueue_script(
        'zibe-enh-admin',
        ZIBE_ENH_URL . 'assets/js/admin.js',
        [ 'jquery', 'wp-color-picker' ],
        ZIBE_ENH_VER,
        true
    );
}

// ─── 处理保存 ─────────────────────────────────────────────────────────────────
add_action( 'admin_post_zibe_enh_save', 'zibe_enh_handle_save' );
function zibe_enh_handle_save() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( '权限不足' );
    check_admin_referer( 'zibe_enh_nonce' );

    $defaults = zibe_enh_defaults();
    $new_opts = [];

    foreach ( $defaults as $key => $default ) {
        if ( $key === 'custom_css' ) {
            $new_opts[ $key ] = isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';
        } elseif ( $key === 'custom_font_url' ) {
            $new_opts[ $key ] = isset( $_POST[ $key ] ) ? esc_url_raw( wp_unslash( $_POST[ $key ] ) ) : '';
        } elseif ( in_array( $key, [ 'primary_color', 'secondary_color', 'link_hover_color', 'card_bg_color', 'gradient_start', 'gradient_end', 'scrollbar_color' ], true ) ) {
            $val = isset( $_POST[ $key ] ) ? sanitize_hex_color( wp_unslash( $_POST[ $key ] ) ) : $default;
            $new_opts[ $key ] = $val ?: $default;
        } elseif ( strpos( $key, 'enable_' ) === 0 || in_array( $key, [ 'sidebar_glass', 'card_hover_lift' ], true ) ) {
            // checkbox：勾选时 POST 中值为 '1'，不勾选时 key 不存在 → 视为 '0'
            $new_opts[ $key ] = isset( $_POST[ $key ] ) ? '1' : '0';
        } else {
            $new_opts[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $default;
        }
    }

    update_option( ZIBE_ENH_OPT, $new_opts );
    wp_redirect( admin_url( 'admin.php?page=zibe-enhancer&saved=1' ) );
    exit;
}

// ─── 设置页面 HTML ─────────────────────────────────────────────────────────────
function zibe_enh_settings_page() {
    $opts = zibe_enh_get();
    $saved = isset( $_GET['saved'] );
    ?>
    <div class="zibe-admin-wrap">
        <div class="zibe-admin-header">
            <span class="dashicons dashicons-art"></span>
            <h1>子比主题美化增强</h1>
            <span class="zibe-version">v<?php echo ZIBE_ENH_VER; ?></span>
        </div>

        <?php if ( $saved ): ?>
        <div class="zibe-notice zibe-notice-success">✅ 设置已保存！效果已实时生效。</div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <?php wp_nonce_field( 'zibe_enh_nonce' ); ?>
            <input type="hidden" name="action" value="zibe_enh_save">

            <div class="zibe-tabs">
                <button type="button" class="zibe-tab active" data-tab="animation">🎬 动画效果</button>
                <button type="button" class="zibe-tab" data-tab="color">🎨 色彩主题</button>
                <button type="button" class="zibe-tab" data-tab="font">🔤 字体排版</button>
                <button type="button" class="zibe-tab" data-tab="layout">🖼️ 布局卡片</button>
                <button type="button" class="zibe-tab" data-tab="misc">⚙️ 其他设置</button>
            </div>

            <!-- 动画效果 -->
            <div class="zibe-panel active" id="tab-animation">
                <div class="zibe-section">
                    <h2>🎬 界面动画效果</h2>
                    <div class="zibe-row">
                        <label>启用页面动画</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="enable_animations" value="1" <?php checked( $opts['enable_animations'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                            <p class="desc">为页面元素添加入场动画（淡入、上浮等）</p>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>动画速度</label>
                        <div class="zibe-control">
                            <select name="animation_speed">
                                <option value="slow" <?php selected( $opts['animation_speed'], 'slow' ); ?>>慢速 (0.6s)</option>
                                <option value="normal" <?php selected( $opts['animation_speed'], 'normal' ); ?>>正常 (0.35s)</option>
                                <option value="fast" <?php selected( $opts['animation_speed'], 'fast' ); ?>>快速 (0.18s)</option>
                            </select>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>鼠标跟随光效</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="enable_mouse_effect" value="1" <?php checked( $opts['enable_mouse_effect'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                            <p class="desc">鼠标移动时卡片产生光影追踪效果</p>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>点击波纹效果</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="enable_ripple" value="1" <?php checked( $opts['enable_ripple'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                            <p class="desc">按钮和链接点击时产生水波纹扩散动画</p>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>顶部进度条</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="enable_loading_bar" value="1" <?php checked( $opts['enable_loading_bar'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                            <p class="desc">页面加载时顶部显示进度条</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 色彩主题 -->
            <div class="zibe-panel" id="tab-color">
                <div class="zibe-section">
                    <h2>🎨 色彩主题定制</h2>
                    <div class="zibe-row">
                        <label>启用色彩主题</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="enable_color_theme" value="1" <?php checked( $opts['enable_color_theme'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                            <p class="desc">关闭后以下所有色彩定制将不生效，使用子比主题默认配色</p>
                        </div>
                    </div>
                    <hr>
                    <div class="zibe-row">
                        <label>主题主色</label>
                        <div class="zibe-control">
                            <input type="text" name="primary_color" value="<?php echo esc_attr( $opts['primary_color'] ); ?>" class="zibe-color-picker">
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>强调色</label>
                        <div class="zibe-control">
                            <input type="text" name="secondary_color" value="<?php echo esc_attr( $opts['secondary_color'] ); ?>" class="zibe-color-picker">
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>链接悬停色</label>
                        <div class="zibe-control">
                            <input type="text" name="link_hover_color" value="<?php echo esc_attr( $opts['link_hover_color'] ); ?>" class="zibe-color-picker">
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>卡片背景色</label>
                        <div class="zibe-control">
                            <input type="text" name="card_bg_color" value="<?php echo esc_attr( $opts['card_bg_color'] ); ?>" class="zibe-color-picker">
                        </div>
                    </div>
                    <hr>
                    <div class="zibe-row">
                        <label>渐变背景</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="enable_gradient_bg" value="1" <?php checked( $opts['enable_gradient_bg'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                            <p class="desc">用渐变色替换页面背景</p>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>渐变起始色</label>
                        <div class="zibe-control">
                            <input type="text" name="gradient_start" value="<?php echo esc_attr( $opts['gradient_start'] ); ?>" class="zibe-color-picker">
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>渐变结束色</label>
                        <div class="zibe-control">
                            <input type="text" name="gradient_end" value="<?php echo esc_attr( $opts['gradient_end'] ); ?>" class="zibe-color-picker">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 字体排版 -->
            <div class="zibe-panel" id="tab-font">
                <div class="zibe-section">
                    <h2>🔤 字体排版</h2>
                    <div class="zibe-row">
                        <label>启用自定义字体</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="enable_custom_font" value="1" <?php checked( $opts['enable_custom_font'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>字体选择</label>
                        <div class="zibe-control">
                            <select name="font_family" id="zibe-font-select">
                                <option value="system" <?php selected( $opts['font_family'], 'system' ); ?>>系统默认</option>
                                <option value="misans" <?php selected( $opts['font_family'], 'misans' ); ?>>MiSans（小米）</option>
                                <option value="noto" <?php selected( $opts['font_family'], 'noto' ); ?>>Noto Sans SC（Google）</option>
                                <option value="alibaba" <?php selected( $opts['font_family'], 'alibaba' ); ?>>阿里巴巴普惠体</option>
                                <option value="custom" <?php selected( $opts['font_family'], 'custom' ); ?>>💡 自定义字体</option>
                            </select>
                        </div>
                    </div>
                    <div class="zibe-row zibe-custom-font-row" style="<?php echo $opts['font_family'] === 'custom' ? '' : 'display:none;'; ?>">
                        <label>字体名称</label>
                        <div class="zibe-control">
                            <input type="text" name="custom_font_name" value="<?php echo esc_attr( $opts['custom_font_name'] ); ?>" placeholder="例如: LXGW WenKai">
                            <p class="desc">CSS font-family 名，与引入的字体文件保持一致</p>
                        </div>
                    </div>
                    <div class="zibe-row zibe-custom-font-row" style="<?php echo $opts['font_family'] === 'custom' ? '' : 'display:none;'; ?>">
                        <label>字体 CSS 链接</label>
                        <div class="zibe-control">
                            <input type="url" name="custom_font_url" value="<?php echo esc_attr( $opts['custom_font_url'] ); ?>" placeholder="https://..." style="width:100%;max-width:460px;">
                            <p class="desc">
                                填写 CSS 文件的完整 URL（如 Google Fonts、CDN 或上传到静态站的 .css）<br>
                                示例：<code>https://cdn.jsdelivr.net/npm/lxgw-wenkai-webfont@1.7.0/style.css</code>
                            </p>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>基础字号 (px)</label>
                        <div class="zibe-control">
                            <input type="range" name="font_size_base" min="12" max="20" value="<?php echo esc_attr( $opts['font_size_base'] ); ?>" class="zibe-range" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span class="range-val"><?php echo esc_html( $opts['font_size_base'] ); ?>px</span>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>行高</label>
                        <div class="zibe-control">
                            <input type="range" name="line_height" min="1.4" max="2.4" step="0.1" value="<?php echo esc_attr( $opts['line_height'] ); ?>" class="zibe-range" oninput="this.nextElementSibling.textContent=this.value">
                            <span class="range-val"><?php echo esc_html( $opts['line_height'] ); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 布局卡片 -->
            <div class="zibe-panel" id="tab-layout">
                <div class="zibe-section">
                    <h2>🖼️ 侧边栏 & 卡片美化</h2>
                    <div class="zibe-row">
                        <label>侧边栏毛玻璃</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="sidebar_glass" value="1" <?php checked( $opts['sidebar_glass'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                            <p class="desc">侧边栏组件使用 backdrop-filter 毛玻璃效果</p>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>模糊强度 (px)</label>
                        <div class="zibe-control">
                            <input type="range" name="sidebar_blur" min="0" max="30" value="<?php echo esc_attr( $opts['sidebar_blur'] ); ?>" class="zibe-range" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span class="range-val"><?php echo esc_html( $opts['sidebar_blur'] ); ?>px</span>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>背景不透明度 (%)</label>
                        <div class="zibe-control">
                            <input type="range" name="sidebar_opacity" min="20" max="100" value="<?php echo esc_attr( $opts['sidebar_opacity'] ); ?>" class="zibe-range" oninput="this.nextElementSibling.textContent=this.value+'%'">
                            <span class="range-val"><?php echo esc_html( $opts['sidebar_opacity'] ); ?>%</span>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>侧边栏圆角 (px)</label>
                        <div class="zibe-control">
                            <input type="range" name="sidebar_border_radius" min="0" max="24" value="<?php echo esc_attr( $opts['sidebar_border_radius'] ); ?>" class="zibe-range" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span class="range-val"><?php echo esc_html( $opts['sidebar_border_radius'] ); ?>px</span>
                        </div>
                    </div>
                    <hr>
                    <div class="zibe-row">
                        <label>卡片悬停上浮</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="card_hover_lift" value="1" <?php checked( $opts['card_hover_lift'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                            <p class="desc">鼠标悬停时卡片上浮并增强阴影</p>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>卡片圆角 (px)</label>
                        <div class="zibe-control">
                            <input type="range" name="card_border_radius" min="0" max="24" value="<?php echo esc_attr( $opts['card_border_radius'] ); ?>" class="zibe-range" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span class="range-val"><?php echo esc_html( $opts['card_border_radius'] ); ?>px</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 其他设置 -->
            <div class="zibe-panel" id="tab-misc">
                <div class="zibe-section">
                    <h2>⚙️ 其他杂项</h2>
                    <div class="zibe-row">
                        <label>美化滚动条</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="enable_scrollbar" value="1" <?php checked( $opts['enable_scrollbar'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>滚动条颜色</label>
                        <div class="zibe-control">
                            <input type="text" name="scrollbar_color" value="<?php echo esc_attr( $opts['scrollbar_color'] ); ?>" class="zibe-color-picker">
                        </div>
                    </div>
                    <div class="zibe-row">
                        <label>显示返回顶部</label>
                        <div class="zibe-control">
                            <label class="zibe-switch">
                                <input type="checkbox" name="enable_back_top" value="1" <?php checked( $opts['enable_back_top'], '1' ); ?>>
                                <span class="zibe-slider"></span>
                            </label>
                        </div>
                    </div>
                    <hr>
                    <div class="zibe-row zibe-row-full">
                        <label>自定义 CSS</label>
                        <div class="zibe-control">
                            <textarea name="custom_css" rows="10" placeholder="/* 在此输入自定义 CSS，优先级最高 */"><?php echo esc_textarea( $opts['custom_css'] ); ?></textarea>
                            <p class="desc">此处 CSS 会附加在所有样式之后，可用于覆盖任何样式。</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="zibe-footer">
                <button type="submit" class="zibe-save-btn">💾 保存所有设置</button>
                <button type="button" class="zibe-reset-btn" onclick="return confirm('确定要重置为默认值吗？')">🔄 恢复默认</button>
            </div>
        </form>
    </div>
    <?php
}

// ─── 插件激活：设置默认值 ────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'zibe_enh_activate' );
function zibe_enh_activate() {
    if ( ! get_option( ZIBE_ENH_OPT ) ) {
        update_option( ZIBE_ENH_OPT, zibe_enh_defaults() );
    }
}
