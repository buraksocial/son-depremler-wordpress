<?php
/**
 * BurakSocial Deprem Verileri Shortcode'u
 * Bu fonksiyon, Boğaziçi Üniversitesi Kandilli Rasathanesi'nin RSS beslemesinden
 * son 24 saatteki deprem verilerini çeker ve bir HTML tablosu olarak gösterir.
 *
 * Shortcode: [buraksocial_deprem]
 */
function buraksocial_render_earthquake_data() {
    date_default_timezone_set('Europe/Istanbul');

    $rss_url = "http://koeri.boun.edu.tr/rss/";
    $rss_content = @file_get_contents($rss_url);

    if ($rss_content === false) {
        return '<p style="color:red; text-align:center; padding:15px 10px;">Boğaziçi Üniversitesi Kandilli Rasathanesi verilerine şu anda ulaşılamıyor.</p>';
    }

    $xml_data = @simplexml_load_string($rss_content);

    if ($xml_data === false) {
        return '<p style="color:red; text-align:center; padding:15px 10px;">Alınan deprem verisi (XML) işlenemedi.</p>';
    }

    $current_time = time();
    $earthquakes = [];

    foreach ($xml_data->channel->item as $item) {
        $publish_timestamp = strtotime((string) $item->pubDate);

        if (($current_time - $publish_timestamp) > (24 * 60 * 60)) {
            continue;
        }

        $title = (string) $item->title;
        $description = (string) $item->description;
        $magnitude = null;

        if (preg_match('/([\d\.]+)\s*\(Mw\)/i', $title, $magnitude_match)) {
            $magnitude = number_format((float) $magnitude_match[1], 1);
        } elseif (preg_match('/([\d\.]+)\s*\(ML\)/i', $title, $magnitude_match)) {
            $magnitude = number_format((float) $magnitude_match[1], 1);
        }

        if ($magnitude === null) {
            continue;
        }

        $location = '-';
        if (preg_match('/\((Mw|ML)\)\s*(.*?)\s*\d{4}\./', $title, $location_match)) {
            $location = trim(preg_replace('/\s+/', ' ', $location_match[2]));
        }

        $depth = '-';
        if (preg_match('/\s([\d\.]+)\s*(km)?\s*$/i', $description, $depth_match)) {
            $depth = number_format((float) $depth_match[1], 1) . ' km';
        }

        $earthquakes[] = [
            'depth'     => $depth,
            'magnitude' => $magnitude,
            'location'  => $location,
            'time'      => date('d.m.Y H:i', $publish_timestamp),
        ];
    }

    if (empty($earthquakes)) {
        return '<p style="text-align:center; padding:15px 10px;">Son 24 saat içerisinde herhangi bir deprem kaydedilmedi.</p>';
    }

    ob_start();
    ?>
    <div class="buraksocial-earthquake-widget">
        <h3>Son Depremler</h3>
        <p class="widget-subtitle">
            Son 24 saat | Kaynak: KOERI - <?php echo date('d.m.Y H:i'); ?>
        </p>
        
        <div class="table-wrapper">
            <table class="earthquake-table">
                <thead>
                    <tr>
                        <th>Derinlik</th>
                        <th>Büyüklük</th>
                        <th>Lokasyon</th>
                        <th>Zaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($earthquakes as $quake): ?>
                    <tr class="<?php echo ($quake['magnitude'] >= 3.0) ? 'high-magnitude' : ''; ?>">
                        <td><?php echo esc_html($quake['depth']); ?></td>
                        <td><?php echo esc_html($quake['magnitude']); ?></td>
                        <td><?php echo esc_html($quake['location']); ?></td>
                        <td><?php echo esc_html($quake['time']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <p class="widget-footnote">
            <em>3.0 ve üzeri büyüklükteki depremler turuncu renkle işaretlenmiştir.</em>
        </p>
    </div>

    <style>
        .buraksocial-earthquake-widget {
            width: 100%;
            max-width: 100%;
            padding: 0 10px;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .buraksocial-earthquake-widget h3 {
            text-align: center;
            margin: 15px 0 10px;
            font-size: 1.3em;
            line-height: 1.4;
        }
        .buraksocial-earthquake-widget .widget-subtitle {
            text-align: center;
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        .buraksocial-earthquake-widget .table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .buraksocial-earthquake-widget .earthquake-table {
            width: 100%;
            min-width: 100%;
            border-collapse: collapse;
            font-size: 16px;
        }
        .buraksocial-earthquake-widget .earthquake-table th {
            background-color: #2c3e50;
            color: #ffffff;
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #ddd;
            font-weight: 500;
            font-size: 0.9em;
        }
        .buraksocial-earthquake-widget .earthquake-table td {
            padding: 10px 8px;
            border: 1px solid #eee;
            text-align: center;
            font-size: 0.9em;
            word-break: break-word;
        }
        .buraksocial-earthquake-widget .earthquake-table td:nth-child(2) {
            font-weight: bold;
        }
        .buraksocial-earthquake-widget .earthquake-table tr.high-magnitude {
            background-color: #ffe0b2;
            font-weight: 500;
        }
        .buraksocial-earthquake-widget .widget-footnote {
            text-align: center;
            font-size: 0.8em;
            color: #999;
            margin-top: 15px;
            padding-bottom: 10px;
            line-height: 1.4;
        }

        /* Mobil Cihazlar için Stil Düzenlemeleri */
        @media screen and (max-width: 480px) {
            .buraksocial-earthquake-widget {
                padding: 0 5px;
            }
            .buraksocial-earthquake-widget h3 {
                font-size: 1.2em;
                margin: 10px 0 8px;
            }
            .buraksocial-earthquake-widget .widget-subtitle,
            .buraksocial-earthquake-widget .widget-footnote {
                font-size: 0.8em;
            }
            .buraksocial-earthquake-widget .earthquake-table {
                font-size: 14px;
            }
            .buraksocial-earthquake-widget .earthquake-table th,
            .buraksocial-earthquake-widget .earthquake-table td {
                padding: 8px 5px;
                font-size: 0.85em;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}

// Yeni shortcode'u WordPress'e kaydet
add_shortcode('buraksocial_deprem', 'buraksocial_render_earthquake_data');
