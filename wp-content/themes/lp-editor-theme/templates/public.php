<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($company_name); ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "<?php echo esc_js($primary_color); ?>",
                        secondary: "<?php echo esc_js($secondary_color); ?>",
                        "background-light": "#F3F4F6",
                        "background-dark": "#111827",
                        "surface-light": "#FFFFFF",
                        "surface-dark": "#1F2937",
                        "text-light": "#374151",
                        "text-dark": "#F3F4F6",
                    },
                    fontFamily: {
                        display: ["'Noto Sans JP'", "sans-serif"],
                        body: ["'Noto Sans JP'", "sans-serif"],
                    },
                },
            },
        };
    </script>
    <style>
        body {
            min-height: max(884px, 100dvh);
        }

        .footer-hours {
            line-height: 1.5;
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: baseline;
            column-gap: 0.2em;
            row-gap: 0.15em;
        }

        .footer-hours-label {
            white-space: nowrap;
        }

        .footer-hours-chunk {
            white-space: nowrap;
            overflow-wrap: anywhere;
        }
    </style>
</head>

<body class="bg-background-light font-body text-text-light antialiased">

    <!-- ヘッダー -->
    <header class="w-full h-16 bg-surface-light shadow-sm border-b border-gray-200">
        <div class="container mx-auto px-4 h-full flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-tight text-gray-900 flex items-center gap-2">
                <?php if (($header_icon_type ?? 'material') === 'image' && !empty($header_icon_image_url)) : ?>
                    <img src="<?php echo esc_url($header_icon_image_url); ?>" alt="" style="width:24px;height:24px;object-fit:contain;">
                <?php else : ?>
                    <span class="material-icons" style="color:<?php echo esc_attr($text_primary_adjusted); ?>"><?php echo esc_html($header_icon); ?></span>
                <?php endif; ?>
                <span><?php echo esc_html($company_name); ?></span>
            </h1>
            <a class="text-sm font-medium bg-primary px-4 py-2 rounded-full shadow-md hover:opacity-80 transition-all flex items-center gap-1.5"
                style="color:<?php echo esc_attr($text_on_primary); ?>" href="#contact">
                <span class="material-icons" style="font-size:18px">mail</span>
                <?php echo esc_html($cta_short); ?>
            </a>
        </div>
    </header>

    <main>
        <!-- ヒーローセクション -->
        <?php
        $hero_text_align = $hero['text_align'] ?? 'bottom';
        $hero_flex_class = 'items-end';
        $hero_padding_class = 'pb-8';
        $hero_gradient_class = 'bg-gradient-to-t from-gray-900/80 via-gray-900/30 to-transparent';

        if ($hero_text_align === 'top') {
            $hero_flex_class = 'items-start';
            $hero_padding_class = 'pt-8';
            $hero_gradient_class = 'bg-gradient-to-b from-gray-900/80 via-gray-900/30 to-transparent';
        } elseif ($hero_text_align === 'center') {
            $hero_flex_class = 'items-center';
            $hero_padding_class = 'py-8';
            $hero_gradient_class = 'bg-gray-900/50';
        }
        ?>
        <section id="hero-section" class="relative h-[84vh] w-full overflow-hidden flex <?php echo esc_attr($hero_flex_class); ?> justify-center">
            <div class="absolute inset-0 z-0">
                <?php if ($hero_image_url) : ?>
                    <img alt="" class="w-full h-full object-cover object-center" src="<?php echo esc_url($hero_image_url); ?>">
                <?php else : ?>
                    <div class="w-full h-full bg-gradient-to-br from-gray-700 to-gray-900"></div>
                <?php endif; ?>
                <div class="absolute inset-0 <?php echo esc_attr($hero_gradient_class); ?>"></div>
            </div>
            <div class="relative z-10 w-full px-4 <?php echo esc_attr($hero_padding_class); ?> text-center text-white">
                <?php if ($hero_badge) : ?>
                    <div class="mb-4 inline-block px-3 py-1 bg-primary/90 rounded-full text-xs font-bold tracking-wide shadow-lg"
                        style="color:<?php echo esc_attr($text_on_primary); ?>">
                        <?php echo esc_html($hero_badge); ?>
                    </div>
                <?php endif; ?>
                <h2 class="text-3xl font-bold leading-tight mb-3 drop-shadow-md">
                    <?php echo wp_kses_post($hero_headline); ?>
                </h2>
                <p class="text-sm text-gray-200 mb-6 drop-shadow-sm font-medium">
                    <?php echo wp_kses_post($hero_subtext); ?>
                </p>
                <a class="flex items-center justify-center w-full max-w-sm mx-auto bg-primary font-bold py-4 rounded-full shadow-lg hover:opacity-80 transition-all"
                    style="color:<?php echo esc_attr($text_on_primary); ?>" href="#contact">
                    <span class="material-icons mr-2">mail</span>
                    <?php echo esc_html($cta_long); ?>
                </a>
            </div>
        </section>

        <!-- ポイント紹介セクション -->
        <section class="py-16 md:py-24 px-4 bg-background-light">
            <div class="max-w-md md:max-w-6xl mx-auto">
                <?php if (!empty($problems_title)) : ?>
                    <h3 class="text-center text-xl md:text-2xl font-bold mb-8 text-gray-800">
                        <?php echo esc_html($problems_title); ?>
                    </h3>
                <?php endif; ?>
                <?php
                $problem_count = count($problems);
                if ($problem_count === 1) {
                    $problems_grid = 'max-w-lg mx-auto';
                } elseif ($problem_count === 2 || $problem_count === 4) {
                    $problems_grid = 'md:grid-cols-2';
                } elseif ($problem_count === 3) {
                    $problems_grid = 'md:grid-cols-3';
                } else {
                    $problems_grid = 'md:grid-cols-3';
                }
                ?>
                <div class="grid grid-cols-1 <?php echo esc_attr($problems_grid); ?> gap-6 md:gap-8">
                    <?php foreach ($problems as $problem) : ?>
                        <div class="bg-surface-light rounded-xl shadow-md relative overflow-hidden">
                            <div class="h-1 w-3/5 mx-auto" style="background-color:<?php echo esc_attr($text_primary_adjusted); ?>"></div>
                            <div class="p-5">
                                <?php if (!empty($problem['image'])) : ?>
                                    <div class="w-full aspect-[16/9] bg-gray-200 rounded-lg overflow-hidden mb-4">
                                        <img alt="" class="w-full h-full object-cover" src="<?php echo esc_url($problem['image']); ?>">
                                    </div>
                                <?php endif; ?>
                                <h4 class="font-bold text-gray-800 mb-2 text-base md:text-lg text-center"><?php echo esc_html($problem['title'] ?? ''); ?></h4>
                                <p class="text-sm md:text-base text-gray-600 leading-relaxed md:text-center"><?php echo esc_html($problem['description'] ?? ''); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 解決セクション（ポイント紹介と同一セクション内） -->
                <?php
                $visible_solutions = array_values(array_filter($solutions ?? array(), function ($s) {
                    return !empty($s['label']) || !empty($s['message_html']) || !empty($s['description_html']) || !empty($s['image']);
                }));
                if (!empty($visible_solutions)) :
                ?>
                    <div class="mt-16 md:mt-20 flex flex-col items-center">
                        <div class="grid gap-6 <?php echo count($visible_solutions) === 2 ? 'md:grid-cols-2 max-w-6xl' : 'max-w-lg'; ?> mx-auto">
                            <?php foreach ($visible_solutions as $solution_item) : ?>
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 md:p-8 rounded-2xl shadow-inner border border-blue-200 text-center">
                                    <?php if (!empty($solution_item['label'])) : ?>
                                        <div class="inline-block bg-secondary text-xs font-bold px-3 py-1 rounded-full mb-3"
                                            style="color:<?php echo esc_attr($text_on_secondary); ?>">
                                            <?php echo esc_html($solution_item['label']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <h4 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:min-h-[5rem] flex flex-col justify-center items-center">
                                        <div>
                                            <?php echo wp_kses_post($solution_item['message_html'] ?? ''); ?>
                                        </div>
                                    </h4>
                                    <?php if (!empty($solution_item['image'])) : ?>
                                        <div class="mt-4 rounded-2xl shadow-lg overflow-hidden max-w-sm md:max-w-md aspect-[16/9] mx-auto">
                                            <img alt="" class="w-full h-full object-cover" src="<?php echo esc_url($solution_item['image']); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($solution_item['image_caption'])) : ?>
                                        <p class="text-xs text-gray-500 mt-2 text-center"><?php echo esc_html($solution_item['image_caption']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-sm md:text-base mt-4 text-gray-700 font-medium leading-relaxed">
                                        <?php echo wp_kses_post($solution_item['description_html'] ?? ''); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- 選ばれる理由セクション -->
        <section class="py-16 md:py-24 bg-white">
            <div class="max-w-md md:max-w-6xl mx-auto px-4">
                <h3 class="text-center text-xl font-bold mb-10 text-gray-800">
                    <?php echo wp_kses_post($reasons_title); ?>
                </h3>
                <div class="space-y-8 md:space-y-0 md:grid md:grid-cols-2 md:gap-8 max-w-md md:max-w-6xl mx-auto">
                    <?php $reason_num = 1;
                    foreach ($reasons as $reason) : ?>
                        <div class="flex flex-col items-center text-center">
                            <?php if (!empty($reason['image'])) : ?>
                                <div class="w-full aspect-[16/9] rounded-2xl overflow-hidden shadow-lg mb-4 relative group">
                                    <img alt="" class="w-full h-full object-cover" src="<?php echo esc_url($reason['image']); ?>">
                                    <div class="absolute top-0 left-0 bg-primary font-bold px-4 py-2 rounded-br-2xl"
                                        style="color:<?php echo esc_attr($text_on_primary); ?>">
                                        <?php echo esc_html($reason['number'] ?? str_pad($reason_num, 2, '0', STR_PAD_LEFT)); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <h4 class="text-lg font-bold text-gray-900 mb-2"><?php echo esc_html($reason['title'] ?? ''); ?></h4>
                            <p class="text-sm md:text-base text-gray-600 leading-relaxed w-full"><?php echo esc_html($reason['description'] ?? ''); ?></p>
                        </div>
                    <?php $reason_num++;
                    endforeach; ?>
                </div>
                <div class="mt-12 text-center">
                    <a class="inline-flex items-center justify-center w-full max-w-xs bg-primary font-bold py-3.5 rounded-lg shadow-md hover:opacity-80 transition-all"
                        style="color:<?php echo esc_attr($text_on_primary); ?>" href="#contact">
                        <span class="material-icons mr-2 text-xl">email</span>
                        <?php echo esc_html($cta_long); ?>
                    </a>
                </div>
            </div>
        </section>

        <!-- ご依頼の流れセクション -->
        <section class="py-16 md:py-24 px-4 bg-background-light">
            <div class="max-w-md md:max-w-6xl mx-auto px-4">
                <h3 class="text-center text-xl font-bold mb-8 text-gray-800">
                    <?php echo esc_html($flow_title); ?>
                </h3>
                <!-- SP: 縦タイムライン / PC: 横並びグリッド -->
                <div class="relative md:grid md:grid-cols-2 lg:grid-cols-4 md:gap-6">
                    <!-- SP用タイムラインライン -->
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 md:hidden"></div>
                    <?php $step_num = 1;
                    foreach ($steps as $step) : ?>
                        <div class="relative pl-12 pb-8 md:pl-0 md:pb-0 md:text-center md:flex md:flex-col">
                            <!-- ステップ番号 -->
                            <div class="absolute left-0 top-0 md:static md:mx-auto md:mb-3 bg-secondary w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shadow-sm"
                                style="color:<?php echo esc_attr($text_on_secondary); ?>">
                                <?php echo esc_html($step_num); ?>
                            </div>
                            <div class="bg-surface-light p-4 md:p-8 rounded-lg shadow-sm border border-gray-100 md:max-w-md lg:max-w-[300px] md:mx-auto md:flex-1 md:h-full w-full">
                                <h4 class="font-bold text-gray-900 mb-2"><?php echo esc_html($step['title'] ?? ''); ?></h4>
                                <p class="text-xs md:text-sm text-gray-500 leading-relaxed"><?php echo esc_html($step['description'] ?? ''); ?></p>
                            </div>
                        </div>
                    <?php $step_num++;
                    endforeach; ?>
                </div>
            </div>
        </section>

        <!-- サービス例セクション -->
        <section class="py-16 md:py-24 bg-white">
            <div class="max-w-md md:max-w-6xl mx-auto px-4">
                <h3 class="text-center text-xl md:text-2xl font-bold mb-6 md:mb-8 text-gray-800">
                    <?php echo esc_html($services_title); ?>
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 auto-rows-fr grid-flow-dense">
                    <?php foreach ($services as $case) :
                        $layout = $case['layout'] ?? 'half';
                        // スマホでは「大」は横長（高さは小と同じ）、PCでは縦横2倍の大きな正方形にする
                        $col_class = ($layout === 'full') ? 'col-span-2 md:row-span-2' : 'col-span-1';
                        $aspect_class = ($layout === 'full') ? 'aspect-[2/1] md:aspect-square' : 'aspect-square';
                    ?>
                        <div class="<?php echo esc_attr($col_class); ?> <?php echo esc_attr($aspect_class); ?> group rounded-xl md:rounded-2xl overflow-hidden shadow-md relative">
                            <?php if (!empty($case['image'])) : ?>
                                <img alt="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" src="<?php echo esc_url($case['image']); ?>">
                            <?php else : ?>
                                <div class="w-full h-full bg-gray-200"></div>
                            <?php endif; ?>
                            <div class="absolute inset-x-0 bottom-0 pt-12 pb-3 px-3 md:pb-5 md:px-5 bg-gradient-to-t from-black/80 via-black/40 to-transparent">
                                <p class="text-white text-xs md:text-base font-bold tracking-wider leading-tight">
                                    <?php echo esc_html($case['caption'] ?? ''); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- お問い合わせセクション -->
        <section class="py-16 md:py-24 px-4" id="contact" style="background-color:#F3F4F6">
            <div class="max-w-md mx-auto">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo esc_html($contact_title); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo wp_kses_post($contact_subtitle); ?></p>
                </div>

                <?php
                // 送信結果メッセージを表示
                $contact_success = get_transient('lp_contact_success_' . $page_id);
                $contact_error = get_transient('lp_contact_error_' . $page_id);
                if ($contact_success) :
                    delete_transient('lp_contact_success_' . $page_id);
                ?>
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center gap-2 text-green-700">
                            <span class="material-icons">check_circle</span>
                            <p class="text-sm font-medium whitespace-pre-line"><?php echo esc_html($contact_success); ?></p>
                        </div>
                    </div>
                <?php elseif ($contact_error) :
                    delete_transient('lp_contact_error_' . $page_id);
                ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center gap-2 text-red-700">
                            <span class="material-icons">error</span>
                            <p class="text-sm font-medium"><?php echo esc_html($contact_error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form id="contact-form" method="post" action="<?php echo esc_url(get_permalink($page_id)); ?>" class="space-y-4 bg-white p-6 md:p-10 rounded-2xl border border-gray-100 shadow-sm">
                    <input type="hidden" name="lp_contact_submit" value="1">
                    <input type="hidden" name="lp_page_id" value="<?php echo esc_attr($page_id); ?>">
                    <?php wp_nonce_field('lp_contact_form', 'lp_contact_nonce'); ?>

                    <?php
                    foreach ($form_fields as $field_index => $field) :
                        $field_type = $field['type'] ?? 'text';
                        $field_label = $field['label'] ?? '';
                        $field_required = !empty($field['required']);
                        $field_placeholder = $field['placeholder'] ?? '';
                        $field_options = $field['options'] ?? '';
                        $field_name = lp_editor_get_form_field_name($field, $field_index);
                    ?>
                        <div class="form-field">
                            <?php if ($field_type !== 'checkbox') : ?>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <?php echo esc_html($field_label); ?>
                                    <?php if ($field_required) : ?><span class="text-red-500 ml-1">*</span><?php endif; ?>
                                </label>
                            <?php endif; ?>

                            <?php if ($field_type === 'textarea') : ?>
                                <textarea name="<?php echo esc_attr($field_name); ?>"
                                    class="w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm py-2 px-3 text-sm"
                                    placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                    rows="4"
                                    <?php echo $field_required ? 'required' : ''; ?>
                                    data-label="<?php echo esc_attr($field_label); ?>"></textarea>
                            <?php elseif ($field_type === 'select') :
                                $options_array = array_filter(array_map('trim', explode("\n", $field_options)));
                            ?>
                                <select name="<?php echo esc_attr($field_name); ?>"
                                    class="w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm py-2 px-3 text-sm"
                                    <?php echo $field_required ? 'required' : ''; ?>
                                    data-label="<?php echo esc_attr($field_label); ?>">
                                    <option value="">選択してください</option>
                                    <?php foreach ($options_array as $opt) : ?>
                                        <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($field_type === 'checkbox') :
                                $options_array = array_filter(array_map('trim', explode("\n", $field_options)));
                                $checkbox_label = !empty($options_array) ? $options_array[0] : $field_label;
                            ?>
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input name="<?php echo esc_attr($field_name); ?>[]"
                                        class="rounded border-gray-300 text-primary shadow-sm"
                                        type="checkbox"
                                        value="<?php echo esc_attr($checkbox_label); ?>"
                                        <?php echo $field_required ? 'required' : ''; ?>
                                        data-label="<?php echo esc_attr($checkbox_label ?: '確認項目'); ?>">
                                    <span><?php echo esc_html($checkbox_label); ?></span>
                                </label>
                            <?php else : ?>
                                <input name="<?php echo esc_attr($field_name); ?>"
                                    class="w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm py-2 px-3 text-sm"
                                    type="<?php echo esc_attr($field_type); ?>"
                                    placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                    <?php echo $field_required ? 'required' : ''; ?>
                                    data-label="<?php echo esc_attr($field_label); ?>">
                            <?php endif; ?>
                            <p class="field-error text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                    <?php endforeach; ?>

                    <button id="submit-btn" class="w-full flex justify-center py-3 px-4 rounded-lg shadow-lg text-sm font-bold bg-primary hover:opacity-90 transition-opacity"
                        style="color:<?php echo esc_attr($text_on_primary); ?>" type="submit">
                        <?php echo esc_html($form_submit_text ?? '送信する'); ?>
                    </button>
                </form>

                <div class="mt-6">
                    <!-- 固定CTA（スマホのみ表示。PCはフッター内に配置） -->
                    <div id="bottom-cta" class="fixed bottom-0 left-0 w-full z-40 pointer-events-none pt-3 px-4 transition-all duration-500 transform translate-y-full opacity-0 md:hidden">
                        <div class="container mx-auto max-w-md flex gap-3 pb-4 pointer-events-auto">
                            <a id="bottom-cta-email" class="flex-1 bg-primary font-bold py-3 rounded-lg flex flex-col items-center justify-center shadow-xl hover:opacity-80 transition-all text-center"
                                style="color:<?php echo esc_attr($text_on_primary); ?>" href="#contact">
                                <?php if (!empty($bottom_bar_email_label)) : ?>
                                    <span class="text-xs font-normal opacity-90 mb-0.5"><?php echo esc_html($bottom_bar_email_label); ?></span>
                                <?php endif; ?>
                                <div class="flex items-center text-sm leading-none">
                                    <span class="material-icons text-lg mr-1">mail</span>
                                    <?php echo esc_html($bottom_bar_email_text); ?>
                                </div>
                            </a>
                            <a id="bottom-cta-phone" class="flex-1 bg-secondary font-bold py-3 rounded-lg flex flex-col items-center justify-center shadow-xl hover:opacity-80 transition-all text-center"
                                style="color:<?php echo esc_attr($text_on_secondary); ?>" href="tel:<?php echo esc_attr($phone); ?>">
                                <?php if (!empty($bottom_bar_phone_label)) : ?>
                                    <span class="text-xs font-normal opacity-90 mb-0.5"><?php echo esc_html($bottom_bar_phone_label); ?></span>
                                <?php endif; ?>
                                <div class="flex items-center text-sm leading-none">
                                    <span class="material-icons text-lg mr-1">phone</span>
                                    <?php echo esc_html($bottom_bar_phone_text); ?>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- フッター -->
    <footer class="py-10 px-4 pb-28 md:pb-10 text-white" style="background-color:#333">
        <div class="max-w-md md:max-w-4xl mx-auto">
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold flex items-center justify-center gap-2 mb-2">
                    <?php if (($header_icon_type ?? 'material') === 'image' && !empty($header_icon_image_url)) : ?>
                        <img src="<?php echo esc_url($header_icon_image_url); ?>" alt="" style="width:24px;height:24px;object-fit:contain;">
                    <?php else : ?>
                        <span class="material-icons text-white"><?php echo esc_html($header_icon); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html($company_name); ?>
                </h2>
                <p class="text-sm text-gray-400"><?php echo esc_html($footer_tagline); ?></p>
            </div>
            <div class="text-sm text-gray-300 space-y-1 md:space-y-0 md:flex md:justify-center md:gap-6 text-center mb-6">
                <p><?php echo esc_html($address); ?></p>
                <p>TEL: <?php echo esc_html($phone_display); ?></p>
                <p class="footer-hours"><span class="footer-hours-label">営業時間:</span><?php echo wp_kses($business_hours_footer_html ?? esc_html($business_hours_full), array("span" => array("class" => true))); ?></p>
            </div>
            <?php if ($sns_links) : ?>
                <div class="flex justify-center gap-4 mb-6">
                    <?php foreach ($sns_links as $sns) : ?>
                        <a href="<?php echo esc_url($sns['url']); ?>"
                            class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center hover:bg-white transition-all shadow-md"
                            style="color:#333">
                            <span class="material-icons text-xl"><?php echo esc_html($sns['icon']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <!-- PC用CTA（フッター内に表示） -->
            <div class="hidden md:flex justify-center gap-4 mb-6">
                <a id="footer-cta-email" class="bg-primary font-bold py-3 px-8 rounded-lg flex flex-col items-center justify-center shadow-xl hover:opacity-80 transition-all text-center"
                    style="color:<?php echo esc_attr($text_on_primary); ?>" href="#contact">
                    <?php if (!empty($bottom_bar_email_label)) : ?>
                        <span class="text-xs font-normal opacity-90 mb-0.5"><?php echo esc_html($bottom_bar_email_label); ?></span>
                    <?php endif; ?>
                    <span class="flex items-center text-sm leading-none">
                        <span class="material-icons text-lg mr-1">mail</span>
                        <?php echo esc_html($bottom_bar_email_text); ?>
                    </span>
                </a>
                <a id="footer-cta-phone" class="bg-secondary font-bold py-3 px-8 rounded-lg flex flex-col items-center justify-center shadow-xl hover:opacity-80 transition-all text-center"
                    style="color:<?php echo esc_attr($text_on_secondary); ?>" href="tel:<?php echo esc_attr($phone); ?>">
                    <?php if (!empty($bottom_bar_phone_label)) : ?>
                        <span class="text-xs font-normal opacity-90 mb-0.5"><?php echo esc_html($bottom_bar_phone_label); ?></span>
                    <?php endif; ?>
                    <span class="flex items-center text-sm leading-none">
                        <span class="material-icons text-lg mr-1">phone</span>
                        <?php echo esc_html($bottom_bar_phone_text); ?>
                    </span>
                </a>
            </div>
            <div class="text-center text-xs text-gray-500 pt-4 border-t border-gray-700">
                <p>&copy; <?php echo esc_html($copyright_year); ?> <?php echo esc_html($company_name); ?> All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- アクセスログ送信 -->
    <?php
    $_lp_post = get_post($page_id);
    $_lp_slug = $_lp_post ? $_lp_post->post_name : '';
    $_is_template_lp = ($_lp_slug === 'template');
    $_lp_page_name = $_is_template_lp ? 'テンプレートLP' : '生成LP（' . $_lp_slug . '）';
    $_lp_id = $_is_template_lp ? '' : $_lp_slug;
    ?>
    <script>
    var lpAccessLogConfig = {
        restUrl: '<?php echo esc_url(rest_url('lp-editor/v1/access-log')); ?>',
        nonce: '<?php echo esc_js(wp_create_nonce('lp_editor_public_api')); ?>',
        pageName: '<?php echo esc_js($_lp_page_name); ?>',
        lpId: '<?php echo esc_js($_lp_id); ?>'
    };
    function sendPublicAccessLog(actionName) {
        try {
            fetch(lpAccessLogConfig.restUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-LP-Nonce': lpAccessLogConfig.nonce
                },
                body: JSON.stringify({
                    action: actionName,
                    page_name: lpAccessLogConfig.pageName,
                    lp_id: lpAccessLogConfig.lpId,
                    screen_size: screen.width + 'x' + screen.height,
                    window_size: window.innerWidth + 'x' + window.innerHeight
                })
            }).catch(function() {});
        } catch (e) {}
    }
    document.addEventListener('DOMContentLoaded', function() {
        sendPublicAccessLog('ページ表示');
    });
    </script>

    <!-- クライアントサイドバリデーション JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contact-form');

            if (!form) return;

            form.addEventListener('submit', function(e) {
                // エラーをクリア
                form.querySelectorAll('.field-error').forEach(el => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
                form.querySelectorAll('.border-red-500').forEach(el => {
                    el.classList.remove('border-red-500');
                });

                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    const value = field.value.trim();
                    const label = field.dataset.label || 'この項目';
                    const errorEl = field.closest('.form-field').querySelector('.field-error');
                    const type = field.type;

                    // チェックボックスは checked で判定
                    if (type === 'checkbox') {
                        if (!field.checked) {
                            isValid = false;
                            if (errorEl) {
                                errorEl.textContent = label + 'にチェックを入れてください';
                                errorEl.classList.remove('hidden');
                            }
                        }
                        return;
                    }

                    // 空チェック
                    if (!value) {
                        isValid = false;
                        field.classList.add('border-red-500');
                        if (errorEl) {
                            errorEl.textContent = label + 'は必須項目です';
                            errorEl.classList.remove('hidden');
                        }
                        return;
                    }

                    // メール形式チェック
                    if (type === 'email') {
                        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailPattern.test(value)) {
                            isValid = false;
                            field.classList.add('border-red-500');
                            if (errorEl) {
                                errorEl.textContent = '正しいメールアドレスを入力してください';
                                errorEl.classList.remove('hidden');
                            }
                        }
                    }

                    // 電話番号形式チェック
                    if (type === 'tel') {
                        const telPattern = /^[\d\-\(\)\s]+$/;
                        if (!telPattern.test(value)) {
                            isValid = false;
                            field.classList.add('border-red-500');
                            if (errorEl) {
                                errorEl.textContent = '電話番号は数字とハイフンのみ使用できます';
                                errorEl.classList.remove('hidden');
                            }
                        } else {
                            // 桁数チェック（数字のみ10〜11桁）
                            const digits = value.replace(/\D/g, '');
                            if (digits.length < 10 || digits.length > 11) {
                                isValid = false;
                                field.classList.add('border-red-500');
                                if (errorEl) {
                                    errorEl.textContent = '電話番号は10〜11桁で入力してください';
                                    errorEl.classList.remove('hidden');
                                }
                            }
                        }
                    }

                    // 数値チェック
                    if (type === 'number') {
                        if (isNaN(value) || value === '') {
                            isValid = false;
                            field.classList.add('border-red-500');
                            if (errorEl) {
                                errorEl.textContent = '数値を入力してください';
                                errorEl.classList.remove('hidden');
                            }
                        }
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    // 最初のエラーフィールドにスクロール
                    const firstError = form.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        firstError.focus();
                    }
                } else {
                    // バリデーション通過 → access-log通知（非同期・ブロックしない）
                    sendPublicAccessLog('お問い合わせ・送信する');
                }
                // isValid = true の場合、フォームは通常通り送信される
            });
        });

        // ボトムCTAの表示制御（ヒーロー通過後に固定表示）
        window.addEventListener('scroll', function() {
            const bottomCta = document.getElementById('bottom-cta');
            if (!bottomCta) return;
            const hero = document.getElementById('hero-section');
            const heroBottom = hero ? hero.getBoundingClientRect().bottom : 0;
            const isPastHero = heroBottom <= 0;

            if (isPastHero) {
                bottomCta.classList.remove('translate-y-full', 'opacity-0');
                bottomCta.classList.add('translate-y-0', 'opacity-100');
            } else {
                bottomCta.classList.add('translate-y-full', 'opacity-0');
                bottomCta.classList.remove('translate-y-0', 'opacity-100');
            }
        });

        // ボトムCTA「メール相談」クリック通知
        var bottomCtaEmail = document.getElementById('bottom-cta-email');
        if (bottomCtaEmail) {
            bottomCtaEmail.addEventListener('click', function() {
                sendPublicAccessLog('メール相談・クリック');
            });
        }

        // ボトムCTA「電話相談」クリック通知
        var bottomCtaPhone = document.getElementById('bottom-cta-phone');
        if (bottomCtaPhone) {
            bottomCtaPhone.addEventListener('click', function() {
                sendPublicAccessLog('電話相談・クリック');
            });
        }

        // フッターCTA「メール相談」クリック通知（PC用）
        var footerCtaEmail = document.getElementById('footer-cta-email');
        if (footerCtaEmail) {
            footerCtaEmail.addEventListener('click', function() {
                sendPublicAccessLog('メール相談・クリック');
            });
        }

        // フッターCTA「電話相談」クリック通知（PC用）
        var footerCtaPhone = document.getElementById('footer-cta-phone');
        if (footerCtaPhone) {
            footerCtaPhone.addEventListener('click', function() {
                sendPublicAccessLog('電話相談・クリック');
            });
        }

        // ページ内アンカーリンク（#contact等）クリック通知
        document.querySelectorAll('a[href^="#"]').forEach(function(a) {
            // ボトムCTAのメール相談は上で個別処理済みなのでスキップ
            if (a.id === 'bottom-cta-email') return;
            a.addEventListener('click', function() {
                var hash = this.getAttribute('href');
                sendPublicAccessLog('ページ内移動・' + hash);
            });
        });
    </script>

</body>

</html>


