<?php

if (!defined('ABSPATH')) {
    exit;
}

class Cliff_Ajax
{
    public static function init(): void
    {
        add_action('wp_ajax_cliff_submit_form', [self::class, 'handle_submit']);
        add_action('wp_ajax_nopriv_cliff_submit_form', [self::class, 'handle_submit']);
    }

    public static function handle_submit(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cliff_form_nonce')) {
            wp_send_json_error(['message' => 'Biztonsági hiba. Kérjük frissítse az oldalt!']);
        }

        $email = sanitize_email($_POST['email'] ?? '');
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Érvénytelen e-mail cím!']);
        }

        // Collect all form fields into a flexible JSON structure
        $skip_keys = ['action', 'nonce', 'cliff_nonce', '_wp_http_referer', 'email', 'alaprajz', 'foto'];
        $form_data = [];

        foreach ($_POST as $key => $value) {
            if (in_array($key, $skip_keys, true)) {
                continue;
            }
            if (is_array($value)) {
                $form_data[$key] = implode(', ', array_map('sanitize_text_field', $value));
            } else {
                $form_data[$key] = sanitize_text_field($value);
            }
        }

        // Handle file uploads
        $alaprajz_url = self::handle_file_upload('alaprajz');
        $foto_url = self::handle_file_upload('foto');

        // Save to database
        global $wpdb;
        $table = $wpdb->prefix . 'cliff_ajanlat';

        $result = $wpdb->insert($table, [
            'email'       => $email,
            'form_data'   => wp_json_encode($form_data, JSON_UNESCAPED_UNICODE),
            'alaprajz_url' => $alaprajz_url,
            'foto_url'    => $foto_url,
        ]);

        if ($result === false) {
            wp_send_json_error(['message' => 'Adatbázis hiba. Kérjük próbálja újra!']);
        }

        // Send email notifications
        self::send_notification_email($email, $form_data, $alaprajz_url, $foto_url);

        wp_send_json_success(['message' => 'Sikeres küldés!']);
    }

    private static function handle_file_upload(string $field): string
    {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $uploaded = wp_handle_upload($_FILES[$field], [
            'test_form' => false,
            'mimes'     => [
                'jpg|jpeg' => 'image/jpeg',
                'png'      => 'image/png',
                'gif'      => 'image/gif',
                'webp'     => 'image/webp',
                'pdf'      => 'application/pdf',
            ],
        ]);

        return $uploaded['url'] ?? '';
    }

    private static function send_notification_email(string $email, array $form_data, string $alaprajz_url, string $foto_url): void
    {
        $to = get_option('cliff_form_email', get_option('admin_email'));
        $subject = 'Új Cliff ajánlatkérés - ' . $email;

        // Build nice labels from field keys
        $label_map = [
            'konyha_kialakitas' => 'Konyha kialakítása',
            'konyha_stilusa'    => 'Konyha stílusa',
            'szinvilag'         => 'Színvilág',
            'konyhapult'        => 'Konyhapult',
            'suto'              => 'Sütő',
            'huto'              => 'Hűtő',
            'elszivo'           => 'Elszívó',
            'fozofelulet'       => 'Főzőfelület',
            'mosogatogep'       => 'Mosogatógép',
            'ingatlan_tipus'    => 'Ingatlan típusa',
            'konyha_meret'      => 'Konyha méret',
            'telefonszam'       => 'Telefonszám',
            'iranyitoszam'      => 'Irányítószám',
            'idopont'           => 'Időpont',
            'adatvedelem'       => 'Adatvédelem elfogadva',
            'marketing'         => 'Marketing hozzájárulás',
        ];

        $rows = [['E-mail', esc_html($email)]];
        foreach ($form_data as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $label = $label_map[$key] ?? ucfirst(str_replace(['_', '-'], ' ', $key));
            $rows[] = [$label, esc_html($value)];
        }
        if ($alaprajz_url) {
            $rows[] = ['Alaprajz', "<a href='" . esc_url($alaprajz_url) . "' style='color:#c8102e;text-decoration:none;font-weight:500;border-bottom:1px solid #c8102e;'>Megtekintés</a>"];
        }
        if ($foto_url) {
            $rows[] = ['Fotó / Skicc', "<a href='" . esc_url($foto_url) . "' style='color:#c8102e;text-decoration:none;font-weight:500;border-bottom:1px solid #c8102e;'>Megtekintés</a>"];
        }

        $data_table = self::render_data_table($rows);

        $admin_body = self::render_email_shell(
            'Új Cliff Online Ajánlatkérés',
            'Új ajánlatkérés érkezett az online űrlapon keresztül.',
            $data_table,
            'Beérkezett: ' . current_time('Y-m-d H:i:s')
        );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Cliff Ajánlatkérés <' . get_option('admin_email') . '>',
            'Reply-To: ' . $email,
        ];

        wp_mail($to, $subject, $admin_body, $headers);

        // Confirmation to customer
        $cc_subject = 'Cliff Konyhák - Ajánlatkérés visszaigazolás';
        $intro = '<p style="margin:0 0 12px;color:#1a1a1a;font-size:16px;">Tisztelt Ügyfelünk!</p>'
            . '<p style="margin:0 0 8px;color:#444;font-size:15px;line-height:1.6;">' . esc_html(cliff_text('s8_body')) . '</p>'
            . '<p style="margin:24px 0 8px;color:#1a1a1a;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Az Ön által megadott adatok</p>';

        $footer = 'Üdvözlettel,&nbsp;<strong style="color:#1a1a1a;">Cliff Konyhák</strong>'
            . '<br><span style="color:#888;">Telefon: ' . esc_html(cliff_text('s8_telefon')) . ' &nbsp;·&nbsp; E-mail: ' . esc_html(cliff_text('s8_email')) . '</span>';

        $cc_body = self::render_email_shell(
            esc_html(cliff_text('s8_title')),
            $intro,
            $data_table,
            $footer
        );

        wp_mail($email, $cc_subject, $cc_body, [
            'Content-Type: text/html; charset=UTF-8',
            'From: Cliff Konyhák <' . get_option('admin_email') . '>',
        ]);
    }

    private static function render_data_table(array $rows): string
    {
        $html = "<table role='presentation' cellpadding='0' cellspacing='0' border='0' style='border-collapse:separate;border-spacing:0;width:100%;background:#ffffff;border:1px solid #ececec;border-radius:8px;overflow:hidden;'>";
        $total = count($rows);
        foreach ($rows as $i => [$label, $value]) {
            $is_last = $i === $total - 1;
            $bg = $i % 2 === 0 ? '#ffffff' : '#fafafa';
            $border = $is_last ? 'none' : '1px solid #f0f0f0';
            $html .= "<tr>"
                . "<td style='padding:14px 20px;background:{$bg};border-bottom:{$border};width:40%;vertical-align:top;color:#888;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;'>"
                . esc_html($label)
                . "</td>"
                . "<td style='padding:14px 20px;background:{$bg};border-bottom:{$border};color:#1a1a1a;font-size:15px;line-height:1.5;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;'>"
                . $value
                . "</td>"
                . "</tr>";
        }
        $html .= "</table>";
        return $html;
    }

    private static function render_email_shell(string $title, string $intro, string $content, string $footer): string
    {
        return '<!DOCTYPE html><html lang="hu"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $title . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f4f4f4;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;-webkit-font-smoothing:antialiased;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f4f4f4;">'
            . '<tr><td align="center" style="padding:32px 16px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.04);overflow:hidden;">'
            . '<tr><td style="height:4px;background:#c8102e;line-height:4px;font-size:0;">&nbsp;</td></tr>'
            . '<tr><td style="padding:36px 36px 20px;">'
            . '<h1 style="margin:0 0 20px;color:#1a1a1a;font-size:22px;font-weight:700;letter-spacing:-0.01em;line-height:1.3;">' . $title . '</h1>'
            . $intro
            . '</td></tr>'
            . '<tr><td style="padding:0 36px 32px;">' . $content . '</td></tr>'
            . '<tr><td style="padding:24px 36px 32px;border-top:1px solid #f0f0f0;background:#fafafa;color:#888;font-size:13px;line-height:1.6;">' . $footer . '</td></tr>'
            . '</table>'
            . '<p style="margin:16px 0 0;color:#aaa;font-size:11px;">© ' . date('Y') . ' Cliff Konyhák</p>'
            . '</td></tr></table></body></html>';
    }
}
