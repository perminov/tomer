<?php
class Vertifire_Row extends Indi_Db_Table_Row {

    /**
     * Pick search query and use it as title
     */
    public function onBeforeSave() {

        // Get `requested_url`'s $_GET params
        parse_str(parse_url($this->requested_url, PHP_URL_QUERY), $_get);

        // use $_get['q'] as title
        $this->title = $_get['q'];
    }

    /**
     * Scrape raw html from the `html_link` url, and parse it
     */
    public function parse() {

        // Foreach result type
        foreach (['organic', 'video', 'ad_top', 'ad_bottom'] as $type) {

            // Reset by-props diff
            foreach (['display_url', 'title', 'description'] as $prop) $this->{$type . '_' . $prop} = '0 / 0 / 0';

            // Reset general diff
            $this->{$type . '0'} = $this->{$type . '1'} = $this->{$type . 'Qty_new'} = 0;
        }

        // Save
        $this->save();

        // If no source file - return false
        if (!$abs = $this->abs('source')) return false;

        // Get html
        $html = file_get_contents($abs);

        // No html got - return false
        if (!$html) return false;

        // Check whether it's mobile version's html
        $mobile = preg_match('~<div id="sfooter"~', $html);

        // Results array, to be flushed as response
        $results = [];

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

            //
            foreach (rootNodes($rso) as $node) {

                // Get top stories html
                if (($top = between('~^<div><g-card[^>]+>~', '</g-card></div>', $node)[0])
                    && !$results['top_stories'] && !$results['organic'] && !$results['featured_snippet']) {

                    // Append inline top stories
                    foreach (between('~<g-card-section[^>]+>~', '</g-card-section>', $top) as $idx => $item)
                        $results['top_stories'] []= [
                            'rank' => $idx + 1,
                            'position' => ++$total,
                            'url' => Indi::rexm('~^<a[^>]+href="([^"]+)"~', $item, 1),
                            'title' => Indi::rexm('~role="heading"><span>([^<]+)</span></div>~', $item, 1),
                            'description' => Indi::rexm('~<cite[^>]*>([^<]+)</cite>~', $item, 1)
                        ];

                    // Append horizontal-scrollable top stories
                    if ($ul = between('~<g-scrolling-carousel[^>]+><div[^>]*><div[^>]*><div[^>]*><ul[^>]*>~', '</ul></div></div>', $top)[0]) {

                        //
                        foreach ($horizontal = between('~<g-inner-card[^>]*>~', '</g-inner-card>', $ul) as $item)
                            $results['top_stories'] []= [
                                'rank' => count($results['top_stories']) + 1,
                                'position' => $total + 1,
                                'url' => Indi::rexm('~^<a[^>]+href="([^"]+)"~', $item, 1),
                                'title' => Indi::rexm('~role="heading">([^<]+)</div>~', $item, 1),
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

                //
                } else if (preg_match('~<div class="srg"~', $node)
                    && $innerHtml = preg_replace('~^<div class="srg"[^>]*>~', '', preg_replace('~</div>$~', '', $node))) {

                    foreach (rootNodes($innerHtml) as $organic) {

                        if (!preg_match('~^<div~', $organic)) continue;

                        preg_match('~<a[^>]+href="([^"]+)"[^>]*>~', $organic, $m1);
                        preg_match('~aria-level="3" role="heading"[^>]*>([^<]+)</div><div[^>]+><span[^>]+>([^<]+)</span></div></a>~', $organic, $m2);

                        //
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
            $div = Indi::rexm('~^<div class="[^"]+">~', $rso, 0);

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
                            'url' => Indi::rexm('~^<a href="([^"]+)"~', $item, 1),
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
                            'url' => Indi::rexm('~^<a style="text-decoration: none" href="([^"]+)"~', $item, 1),
                            'title' => Indi::rexm('~role="heading">([^<]+)</div>~', $item, 1),
                            'description' => Indi::rexm('~<cite>([^<]+)</cite>~', $item, 1)
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

                    //
                    if (count($results['video'])) $total ++;

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

        // Save
        $this->assign([
            'new_results' => json_encode($results),
            'sp' => 'y'
        ])->save();
        
        // Return
        return true;
    }

    /**
     * Compare old parser results with new parser results
     */
    public function compare($type = null, $mode = null, $prop = null) {

        // Decode results
        $parser['old'] = json_decode($this->results, true);
        $parser['new'] = json_decode($this->new_results, true);

        // If $type arg is given - walk though results of given type only
        $typeA = $type ? [$type] : ['organic', 'video', 'ad_top', 'ad_bottom'];

        // If $prop arg is given - look at given prop's results only
        $propA = $prop ? [$prop] : ['display_url', 'title', 'description'];

        // If $mode arg is given, and it is 'old' or 'new' - return old/new parser results of given type
        if (in($mode, ['old', 'new'])) return $parser[$mode][$type];

        // Foreach result type
        foreach ($typeA as $type) {

            // Foreach parser (e.g. 'old' and 'new')
            foreach ($parser as $version => &$res) {

                // Get qty of results
                $qty = count($res[$type]);

                // Set qty
                $this->{$type . 'Qty_' . $version} = $qty;

                // Foreach result
                for ($i = 0; $i < $qty; $i++) {

                    // Get url
                    $url = $res[$type][$i]['url'];

                    // Collect urls
                    $urlA[$type]['url'][$version] []= str_replace('&amp;', '&', $url);

                    // Unset rank and position
                    unset ($res[$type][$i]['rank'], $res[$type][$i]['position']);

                    // Append same item to the ending of $items array, but using $url as a key
                    $res[$type][$url] = $res[$type][$i];

                    // Unset current item and 'url' key within appended item
                    unset($res[$type][$i], $res[$type][$url]['url']);
                }
            }

            // Set qty of results, that are in old parser results, but are not in new
            $this->{$type.'0'} = count($diff[$type.'0'] = array_diff($urlA[$type]['url']['old'] ?: [], $urlA[$type]['url']['new'] ?: []));

            // Set qty of results, that are in new parser results, but are not in old
            $this->{$type.'1'} = count($diff[$type.'1'] = array_diff($urlA[$type]['url']['new'] ?: [], $urlA[$type]['url']['old'] ?: []));

            // If $mode arg is given and is 1 or 0, and $prop arg not given - return urls
            if (in($mode, [0, 1]) && !$prop) return $diff[$type.$mode];

            // Foreach prop that we should compare - reset to zero-values
            foreach ($propA as $prop) $this->{$type . '_' . $prop} = [0, 0, 0];

            // For results, having same urls in both new and old parser's results
            foreach (array_intersect($urlA[$type]['url']['old'] ?: [], $urlA[$type]['url']['new'] ?: []) as $url) {

                // Foreach prop that we should compare
                foreach ($propA as $prop) {

                    // Shortcuts
                    $old = $parser['old'][$type][$url][$prop];
                    $new = $parser['new'][$type][$url][$prop];

                    // Detect diff type
                    $idx = false; if ($old && !$new) $idx = 0; else if (!$old && $new) $idx = 2; else if ($old != $new) $idx = 1;

                    // Diff type is not false
                    if ($idx !== false) {

                        // If both results are not empty, but not equal
                        if ($idx == 1) {

                            // Detect similarity
                            similar_text($old, $new, $sim);

                            // If similarity is less than 95% -
                            if ($sim < 50) {

                                // Increment counter for that certain diff type
                                $this->_modified[$type . '_' . $prop][$idx] ++;

                                // Collect
                                $cmp[$type][$prop][$idx][$url] = [
                                    'old' => $old,
                                    'new' => $new,
                                    'sim' => $sim
                                ];
                            }

                        // Else
                        } else {

                            // Increment counter for that certain diff type
                            $this->_modified[$type . '_' . $prop][$idx] ++;

                            // Collect
                            $cmp[$type][$prop][$idx][$url] = [
                                'old' => $old,
                                'new' => $new
                            ];
                        }
                    }
                }
            }

            // If $prop arg wa given - return diff for that certain prop
            if (func_num_args() > 2) return $cmp[$type][$prop][$mode];

            // Foreach prop that we should compare - join with ' / '
            foreach ($propA as $prop) $this->{$type . '_' . $prop} = im($this->{$type . '_' . $prop}, ' / ');
        }

        // Save
        $this->save();
    }
}