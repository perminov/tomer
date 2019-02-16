<?php
class Admin_ExampleController extends Indi_Controller_Admin {

    public function parseAction() {

        // Check parser type
        /*if (!array_key_exists('google', Indi::uri()))
            jflush(false, 'Only google results parsing is supported');

        if (false) {

            // Get raw request body
            $body = file_get_contents('php://input');

            // Check that request body is not empty
            if (!strlen($body)) jflush(false, 'Request body is empty');

            // Try to JSON-decode request body
            if (!$json = json_decode($body)) jflush(false, 'Request body is not a valid JSON');

            // Try to pick request's 'html_gz' param (which is base64-encoded)
            if (!$base64_encoded = $json['html_gz'])
                jflush(false, 'Request JSON does not contain "html_gz" key, or it\'s value is empty');

            // Try to decode base64-encoded param
            if (!$gzipped = base64_decode($base64_encoded))
                jflush(false, 'Unable to base64-decode the request json\'s "html_gz" param');

            // Try to decode base64-encoded param
            if (!$html = gzdecode($gzipped))
                jflush(false, 'Unable to un-gzip the request json\'s "html_gz" param');

        // Example raw html
        } else $html = file_get_contents(DOC . STD . '/www/data/parser/google/example1.html'); */

        // Get html
        $html = $this->row->html;

        // Results array, to be flushed as response
        $results = [];

        // Get time, spent on arriving here
        $init = mt();

        // Total counter
        $total = 0;

        // Get 'ad_top' elements
        $ad_top = between('~<li class="ads-ad" data-bg="1" data-hveid="[^"]+">~', '</li>',
            between('~<div id="taw">~', '</div><div class="med" id="res" role="main">', $html)[0]);

        // Foreach found
        foreach ($ad_top as $idx => $_) {

            // Pick url, display_url and title
            preg_match('~<div class="ad_cclk"><a style="display:none"[^>]+></a>'
                . '<a class="[^"]+" href="([^"]+)"[^>]+><h3 class="[^"]+">([^<]+)</h3>'
                . '<br><div class="ads-visurl"><span class="[^"]+">Ad</span>'
                . '<cite class="[^"]+">([^<]+)</cite>‎</div></a>~', $_, $m1);

            // Add to results
            $results['ad_top'] []= [
                'rank' => $idx + 1,
                'position' => ++$total,
                'url' => $m1[1],
                'display_url' => $m1[3],
                'title' => $m1[2],
                'description' => strip_tags(between('~<div class="[^"]+ads-creative">~', '</div>', $_)[0])
            ];
        }

        // Get #rso-node contents
        $rso = between('~<div eid="[^"]+" id="rso">~', '</div><!--z-->',
            between('~<div class="med" id="res" role="main">~', '</div><div id="bottomads"', $html)[0])[0];

        // Get opening div
        $div = Indi::rexm('~^<div class="[^"]+">~', $rso, 0);

        // Get groups of results
        $groupA = explode('</div>' . $div, '</div>' . $rso . $div); array_shift($groupA); array_pop($groupA);

        foreach ($groupA as $groupI) {

            // If group contains results, represented as a carousel
            if ($items = between('~<g-scrolling-carousel[^>]+><div[^>]+><div[^>]+><div[^>]+>~', '</div></div></div><g-left-button', $groupI)[0]) {

                // Get result type
                $type = $results['organic'] ? 'videos' : 'top_stories';

                // Get array of items' html
                $itemA = between('~<g-inner-card[^>]*>~', '</g-inner-card>', $items);

                // Foreach item
                foreach ($itemA as $idx => $item) {

                    // If result type is 'top_stories' - pick params and append into $results array
                    if ($type == 'top_stories') $results[$type] []= [
                        'rank' => $idx + 1,
                        'position' => ++$total,
                        'url' => Indi::rexm('~^<a style="text-decoration: none" href="([^"]+)"~', $item, 1),
                        'title' => Indi::rexm('~role="heading">([^<]+)</div>~', $item, 1),
                        'description' => Indi::rexm('~<cite>([^<]+)</cite>~', $item, 1)
                    ];

                    // Else if result type is 'videos'
                    else if ($type = 'videos') {

                        // Pick props
                        preg_match('~^<div class="[^"]+"><a href="([^"]+)"~', $item, $m);
                        preg_match('~aria-level="3" role="heading"[^>]*>([^<]+)</div></div>'
                            . '</a><div class="[^"]+"><div class="[^"]+" style="[^"]+">([^<]*)</div>'
                            . '</div><div class="[^"]+"><div class="[^"]+" style="[^"]+">'
                            . '<span class="[^"]+" style="[^"]+">[^<]*</span>~', $item, $m2);

                        // Assign and append
                        $results[$type] []= [
                            'rank' => $idx + 1,
                            'position' => ++$total,
                            'url' => $m[1],
                            'title' => $m2[1],
                            'description' => strip_tags($m2[2]),
                        ];
                    }
                }

            // Else it's organic results
            } else if ($itemA = between('~<div class="g"><!--m-->~', '<!--n--></div>', $groupI)) {

                // Foreach
                foreach ($itemA as $idx => $item) {

                    // Capture data
                    preg_match('~<div class="rc"><div class="r">'
                        . '<a href="([^"]+)" ping="([^"]+)"><h3 class="[^"]+">([^<]+)</h3>'
                        . '<br><div class="[^"]+"><cite class="[^"]+">([^<]+)</cite></div></a>~', $item, $m);

                    // Assign and append
                    $results['organic'] []= [
                        'rank' => count($results['organic']) + 1,
                        'position' => ++$total,
                        'url' => $m[1],
                        'display_url' => $m[4],
                        'title' => $m[3],
                        'description' => strip_tags(array_shift(explode('</span>', array_pop(explode('<div class="s"><div><span class="st">', $item)))))
                    ];
                }

            // Else if it's twitter results
            } else if (preg_match('~<div class="g"><div class="s"><div class="[^"]+"><div class="r"><h3 class="[^"]+"><g-link>~', $groupI)) {

                // Pick props
                preg_match('~<g-link><a href="([^"]+)"[^>]+>([^<]+)</a></g-link>~', $groupI, $m1);
                preg_match('~<cite[^>]+>([^<]+)</cite>~', $groupI, $m2);

                // Assign and append
                $results['organic'] []= [
                    'rank' => count($results['organic']) + 1,
                    'position' => ++$total,
                    'url' => $m1[1],
                    'display_url' => $m2[1],
                    'title' => $m1[2],
                    'description' => strip_tags(between(
                        '~<g-inner-card[^>]*><!--m--><div class="tw-res"[^>]*><div class="[^"]+" aria-level="3" role="heading">~',
                        '~</div><div class="[^"]+"><div><span class="f">~', $groupI)[0])
                ];

            // Else if it's snack_pack results
            } else if (preg_match('~id="lu_map"~', $groupI)) {

                // Get items
                foreach(between('~<!--m--><div[^>]+><div[^>]+>~', '</div></div><!--n-->', $groupI) as $item) {

                    // Pick props
                    preg_match('~data-cid="([^"]+)"~', $item, $m1);
                    preg_match('~href="([^"]+)" data-ved="[^"]+" ping="[^"]+"~', $item, $m2);
                    preg_match('~<div class="dbg0pd" aria-level="3" role="heading"><span>([^<]+)</span></div>~', $item, $m3);
                    $desc = between('~<span class="[a-zA-Z0-9]+__details[^"]*">~', '</span></div></a>', $item)[0];

                    // Assign and append
                    $results['snack_pack'] []= [
                        'rank' => count($results['snack_pack']) + 1,
                        'position' => ++$total,
                        'cid' => $m1[1],
                        'url' => $m2[1],
                        'title' => $m3[1],
                        'description' => strip_tags(preg_replace('~__wrapped">~', '$0 ', $desc))
                    ];
                }
            }
        }

        // Try to find 'ad_bottom' elements
        $ad_bottom = between('~<li class="ads-ad" data-bg="1" data-hveid="[^"]+">~', '</li>',
            between('~<div id="bottomads">~', '</div><div class="med" id="extrares">', $html)[0]);

        // Foreach found
        foreach ($ad_bottom as $idx => $_) {

            // Pick url, display_url and title
            preg_match('~<div class="ad_cclk"><a style="display:none"[^>]+></a>'
                . '<a class="[^"]+" href="([^"]+)"[^>]+><h3 class="[^"]+">([^<]+)</h3>'
                . '<br><div class="ads-visurl"><span class="[^"]+">Ad</span>'
                . '<cite class="[^"]+">([^<]+)</cite>‎</div></a>~', $_, $m1);

            // Add to results
            $results['ad_bottom'] []= [
                'rank' => $idx + 1,
                'position' => ++$total,
                'url' => $m1[1],
                'display_url' => $m1[3],
                'title' => $m1[2],
                'description' => strip_tags(between('~<div class="[^"]+ads-creative">~', '</div>', $_)[0])
            ];
        }

        // Try to find 'related' elements
        $related = between('~<p class="[^"]+"><a href="/search\?q=[^"]+">~', '</a></p>',
            between('~<div id="brs"[^>]*><g-section-with-header[^>]*~', '</g-section-with-header>',
            between('~<div class="med" id="extrares">~', '</div><div><div id="foot"', $html)[0])[0]);

        // Foreach found - assign to results
        foreach ($related as $idx => $_) $results['related'] []= [
            'rank' => $idx + 1,
            'position' => ++$total,
            'title' => strip_tags($_),
        ];

        // If right block is given
        if (($rhs = between('~<div id="rhs">~', '<div id="bfoot">', $html)[0])
            && $title = strip_tags(between('~<div class="kno-ecr-pt kno-fb-ctx gsmt"[^>]+>~', '</div>', $rhs)[0])) {

            // Pick props
            preg_match('~<!--m--><div><a class="[^"]*ellip kno-fb-ctx" role="button" href="([^"]+)"[^>]*>~', $rhs, $m1);
            preg_match('~<span class="ellip">([^<]+)</span></a></div><!--n--></div>~', $rhs, $m2);

            // Assign and append
            $results['featured_snippet'] = [
                'title' => $title,
                'url' => $m1[1],
                'display_url' => $m2[1],
                'description' => between('~<div class="kno-rdesc[^"]+"[^>]*><div><h3[^>]+>[^<]+</h3><span>~', '</span>', $rhs)[0]
            ];
        }

        // Build response
        $response = ['status' => 1, 'results' => $results];

        // View response
        jflush(true, '<textarea style="width: 500px; height: 400px;">' 
            . 'Init time ' . $init . "\n"
            . 'Parse time ' . mt() . "\n"
            . 'Memory usage ' . mu() . "\n"
            . 'Memory peak usage ' . mpu() . "\n"
            . print_r($response, true) 
            . '</textarea>');

        // Flush response
        // echo json_encode($response); exit;
    }
}