<?php
// Load auxiliary functions
require 'parser-lib.php';

// Set error reporting
error_reporting (E_ALL & ~E_NOTICE);

// Get headers
$headers = apache_request_headers();

// Get compression type
$compression = $headers['Content-Encoding'];

// Get raw request body
$body = file_get_contents('php://input');

// Check that request body is not empty
if (!strlen($body)) jflush(false, 'Request body is empty');

// If compression is brotli
if ($compression == 'br') {
    
    // Try to decode brotli-compressed contents
    if (!$html = brotli_uncompress($body)) jflush(false, 'Unable to un-brotli the request body');

// Else if compression is gz
} else if ($compression == 'gzip' || $compression == 'x-gzip') {

    // Try to decode gz-compressed contents
    if (!$html = gzdecode($body)) jflush(false, 'Unable to un-gzip the request body');
    
// Else assume that content is not compressed html
} else $html = $body;

// Check whether it's mobile version's html
$mobile = preg_match('~<div id="sfooter"~', $html);

// Results array, to be flushed as response
$results = [];

// Get time, spent on arriving here
$init = mt();

// Total counter
$total = 0;

// Pick total results count
if ($stats = between('~<div id="resultStats">~', '<nobr>', $html)[0])
    $results['total_results'] = preg_replace('~[^0-9]~', '', $stats);

// If it's mobile results
if ($mobile) {

    // Get 'ad_top' elements
    $ad_top = between('~<li[^>]+class="ads-fr"[^>]+data-bg="1">~', '</li>',
        between('~<div[^>]+id="tads"[^>]+><h1[^>]*>[^<]*</h1><ol>~', '</ol></div></div>', $html)[0]);

    // Foreach found
    foreach ($ad_top as $idx => $_) {

        // Pick url, display_url and title
        preg_match('~<div[^>]*><div[^>]*><div[^>]*>'
            . '<a[^>]+href="([^"]+)"[^>]*><div[^>]+role="heading"[^>]*>([^<]+)</div>'
            . '<div[^>]*><span[^>]*>[^<]*</span><span[^>]*>([^<]+)</span></div>~', $_, $m1);

        // Add to results
        $results['ad_top'] []= [
            'rank' => $idx + 1,
            'position' => ++$total,
            'url' => $m1[1],
            'display_url' => $m1[3],
            'title' => $m1[2],
            'description' => strip_tags(between('~<hr[^>]*><div[^>]*><div[^>]*>~', '</div></div>', $_)[0])
        ];
    }

    // Get #rso-node contents
    $rso = between('~<div id="rso">~', '</div></div></div><div class="med" id="extrares">', $html)[0];

    // Foreach root node
    foreach (rootNodes($rso) as $node) {

        // Get top stories html
        if (($top = between('~^<div><g-card[^>]+>~', '</g-card></div>', $node)[0])
            && !$results['top_stories'] && !$results['organic'] && !$results['featured_snippet']) {

            // Append inline top stories
            foreach (between('~<g-card-section[^>]+>~', '</g-card-section>', $top) as $idx => $item)
                $results['top_stories'] []= [
                    'rank' => $idx + 1,
                    'position' => ++$total,
                    'url' => rexm('~^<a[^>]+href="([^"]+)"~', $item, 1),
                    'title' => rexm('~role="heading"><span>([^<]+)</span></div>~', $item, 1),
                    'description' => rexm('~<cite[^>]*>([^<]+)</cite>~', $item, 1)
                ];

            // Append horizontal-scrollable top stories
            if ($ul = between('~<g-scrolling-carousel[^>]+><div[^>]*><div[^>]*><div[^>]*><ul[^>]*>~', '</ul></div></div>', $top)[0]) {

                // Foreach horizontal card
                foreach ($horizontal = between('~<g-inner-card[^>]*>~', '</g-inner-card>', $ul) as $item)
                    $results['top_stories'] []= [
                        'rank' => count($results['top_stories']) + 1,
                        'position' => $total + 1,
                        'url' => rexm('~^<a[^>]+href="([^"]+)"~', $item, 1),
                        'title' => rexm('~role="heading">([^<]+)</div>~', $item, 1),
                    ];

                //
                if ($horizontal) $total ++;
            }

        // Else if it's a snack_pack
        } else if (preg_match('~<div class="[^"]*tile-container[^"]*"~', $node)) {

            // Get items
            foreach(innerHtml('~<div class="rl-qs-crs-t tile-container[^"]*"[^>]*>~', $node) as $item) {

                // Pick props
                preg_match('~data-cid="([^"]+)"~', $item, $m1);
                preg_match('~href="(https[^"]+)"~', $item, $m2);
                preg_match('~<div[^>]*aria-level="3" role="heading"><span>([^<]+)</span></div>'
                    . '<div><span>([^<]*)</span></div><div><div[^>]*>([^<]*)</div></div>~', $item, $m3);

                // Assign and append
                $results['snack_pack'] []= [
                    'rank' => count($results['snack_pack']) + 1,
                    'position' => ++$total,
                    'cid' => $m1[1],
                    'url' => $m2[1],
                    'title' => $m3[1],
                    'description' => trim($m3[2] . ' ' . $m3[3])
                ];
            }

        // Else if it's a featured snippet item
        } else if (preg_match('~<div class="kp-blk knowledge-panel[^"]*"~', $node)) {

            // Get .kp-body contents
            $kpbody = between('~<div class="kp-body">~', '</div></div></div><g-immersive-footer>', $node)[0];

            // Pick url
            preg_match('~<!--m--><div><a[^>]+href="([^"]+)"~', $kpbody, $m);

            // Build featured_snippet data
            $results['featured_snippet'] = [
                'title' => strip_tags(between('~<div class="kno-ecr-pt[^"]+"[^>]+>~', '</div>',
                    between('~<div class="kp-hc">~', '</div><div class="kp-body">', $node)[0]
                )[0]),
                'url' => $m[1],
                'display_url' => strip_tags(between('~<!--m--><div><a[^>]+>~', '</a></div><!--n-->', $kpbody)[0]),
                'description' => strip_tags(between('~</h3><span>~', '~</span></?(span|div)>~',
                    between('~<!--m--><div[^>]*><div[^>]*><div[^>]*><div[^>]*>~', '</div></div></div></div><!--n-->', $kpbody)[0])[0]),
            ];

        // Else if it is organic results node
        } else if (preg_match('~<div class="srg"~', $node)
            && $innerHtml = preg_replace('~^<div class="srg"[^>]*>~', '', preg_replace('~</div>$~', '', $node))) {

            // Foreach root node
            foreach (rootNodes($innerHtml) as $organic) {

                // If root node is not a div - skip
                if (!preg_match('~^<div~', $organic)) continue;

                // Pick props
                preg_match('~<a[^>]+href="([^"]+)"[^>]*>~', $organic, $m1);
                preg_match('~aria-level="3" role="heading"[^>]*>([^<]+)</div><div[^>]+><span[^>]+>([^<]+)</span></div></a>~', $organic, $m2);

                // Assign and append
                $results['organic'] []= [
                    'rank' => count($results['organic']) + 1,
                    'position' => ++$total,
                    'url' => $m1[1],
                    'display_url' => $m2[2],
                    'title' => $m2[1],
                    'description' => strip_tags(between('~</a></div><hr[^>]+>~', '<div style="margin-top:16px"></div>', $organic)[0])
                ];
            }


            // Else if it's videos
        } else if ($videos = between('~<div><g-card[^>]+id="tscffb"[^>]+><div class="mnr-c">~', '</div></g-card></div>', $node)[0]) {

            // Pick description and display_url
            preg_match('~^<div class="g card-section"><!--m--><div[^>]*><div class="rc"><div[^>]*>'
                . '<div[^>]*><h3[^>]*><a[^>]*href="([^"]*)"[^>]*>([^<]*)</a></h3></div><div[^>]*>'
                . '<div[^>]*><cite[^>]*>([^<]*)</cite>~', $videos, $m0);

            // If group contains results, represented as a carousel
            if ($items = between('~<g-scrolling-carousel[^>]+><div[^>]+><div[^>]+><div[^>]*><ul[^>]+>~', '</ul></div></div>', $videos)[0]) {

                // Get array of items' html
                $itemA = between('~<g-inner-card[^>]*>~', '</g-inner-card>', $items);

                // Foreach item
                foreach ($itemA as $idx => $item) {

                    // Pick props
                    preg_match($_ = '~^<a[^>]+href="([^"]+)"[^>]+><div[^>]*><div[^>]*>~', $item, $m1);
                    preg_match('~aria-level="3" role="heading"[^>]*>([^<]+)</div>~', $item, $m2);

                    // Assign and append
                    $results['video'] []= [
                        'rank' => count($results['video']) + 1,
                        'position' => $total + 1,
                        'url' => $m1[1],
                        'display_url' => $m0[3],
                        'title' => $m2[1],
                        'description' => $m0[2]
                    ];
                }

                //
                if ($results['video']) $total ++;
            }
        }
    }

    // Get 'ad_bottom' elements
    $ad_bottom = between('~<li[^>]+class="ads-fr"[^>]+data-bg="1">~', '</li>',
        between('~<div[^>]+id="tadsb"[^>]+><h1[^>]*>[^<]*</h1><ol>~', '</ol></div></div>', $html)[0]);

    // Foreach found
    foreach ($ad_bottom as $idx => $_) {

        // Pick url, display_url and title
        preg_match('~<div[^>]*><div[^>]*><div[^>]*>'
            . '<a[^>]+href="([^"]+)"[^>]*><div[^>]+role="heading"[^>]*>([^<]+)</div>'
            . '<div[^>]*><span[^>]*>[^<]*</span><span[^>]*>([^<]+)</span></div>~', $_, $m1);

        // Add to results
        $results['ad_bottom'] []= [
            'rank' => $idx + 1,
            'position' => ++$total,
            'url' => $m1[1],
            'display_url' => $m1[3],
            'title' => $m1[2],
            'description' => strip_tags(between('~<hr[^>]*><div[^>]*><div[^>]*>~', '</div></div>', $_)[0])
        ];
    }

// Else if it's desktop results
} else {

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
    $div = rexm('~^<div class="[^"]+">~', $rso, 0);

    // Get groups of results
    $groupA = explode('</div>' . $div, '</div>' . $rso . $div); array_shift($groupA); array_pop($groupA);

    // Foreach group
    foreach ($groupA as $groupI) {

        // If group contains results, having .dbsr css class
        if ($items = innerHtml('~<div class="dbsr"[^>]*>~', $groupI)) {

            // Foreach result item
            foreach ($items as $item) {

                // Pick props
                preg_match('~</div></div><div[^>]+>(.+)?(?=</div>)</div></div></div></a>~', $item, $m);

                // Assign and append
                $results['top_stories'] []= [
                    'rank' => $idx + 1,
                    'position' => ++$total,
                    'url' => rexm('~^<a href="([^"]+)"~', $item, 1),
                    'title' => between('~<div class="[^"]+" style="-webkit-line-clamp:2"><div class="[^"]+" style="-webkit-line-clamp:2">~', '</div></div>', $item)[0],
                    'description' => strip_tags(preg_replace('~</span><span[^>]*>~', ' ', $m[1]))
                ];
            }

        // Else if group contains results, represented as a carousel
        } if ($items = between('~<g-scrolling-carousel[^>]+><div[^>]+><div[^>]+><div[^>]+>~', '</div></div></div><g-left-button', $groupI)[0]) {

            // Get result type
            $type = $results['organic'] ? 'video' : 'top_stories';

            // Get array of items' html
            $itemA = between('~<g-inner-card[^>]*>~', '</g-inner-card>', $items);

            // Foreach item
            foreach ($itemA as $idx => $item) {

                // If result type is 'top_stories' - pick params and append into $results array
                if ($type == 'top_stories') $results[$type] []= [
                    'rank' => $idx + 1,
                    'position' => ++$total,
                    'url' => rexm('~^<a style="text-decoration: none" href="([^"]+)"~', $item, 1),
                    'title' => rexm('~role="heading">([^<]+)</div>~', $item, 1),
                    'description' => rexm('~<cite>([^<]+)</cite>~', $item, 1)
                ];

                // Else if result type is 'video'
                else if ($type = 'video') {

                    // Pick props
                    preg_match('~^<div class="[^"]+"><a href="([^"]+)"~', $item, $m);
                    preg_match('~aria-level="3" role="heading"[^>]*>([^<]+)</div></div>'
                        . '</a><div class="[^"]+"><div class="[^"]+" style="[^"]+">([^<]*)</div>'
                        . '</div><div class="[^"]+"><div class="[^"]+" style="[^"]+">'
                        . '<span class="[^"]+" style="[^"]+">[^<]*</span>~', $item, $m2);

                    // Assign and append
                    $results[$type] []= [
                        'rank' => $idx + 1,
                        'position' => $total + 1,
                        'url' => $m[1],
                        'title' => $m2[1],
                        'description' => strip_tags($m2[2]),
                    ];
                }
            }

            // If at least single video found - increment $total counter
            if (count($results['video'])) $total ++;

        // Else it's organic results
        } else if ($itemA = between('~<div class="g"><!--m-->~', '<!--n--></div>', $groupI)) {

            // Foreach
            foreach ($itemA as $idx => $item) {

                // Capture data
                preg_match('~<div class="rc"><div class="r">'
                    . '<a href="([^"]+)" ping="([^"]+)"><h3 class="[^"]+">([^<]+)</h3>'
                    . '<br><div class="[^"]+"><cite class="[^"]+">([^<]+)</cite></div></a>~', $item, $m);

                // Get description
                $desc = explode('<div class="s"><div><span class="st">', $item);
                $desc = explode('</span>', array_pop($desc));
                $desc = strip_tags(array_shift($desc));
                    
                // Assign and append
                $results['organic'] []= [
                    'rank' => count($results['organic']) + 1,
                    'position' => ++$total,
                    'url' => $m[1],
                    'display_url' => $m[4],
                    'title' => $m[3],
                    'description' => $desc
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
$related = $mobile
    ? between('~<a class="[^"]+" href="/search\?[^"]+"[^>]*>~', '~</a>~',
        between('~<div class="med" id="extrares">~', '</div></div></div><div id="sfooter"', $html)[0])
    : between('~<p class="[^"]+"><a href="/search\?q=[^"]+">~', '</a></p>',
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

// Set json-encoding flags
$flags = JSON_UNESCAPED_UNICODE;

// Make pretty
if ($headers['Accept-Format'] == 'pretty') $flags |= JSON_PRETTY_PRINT;

// Flush response
echo json_encode($response, $flags); exit;
