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
        
        // Get main results
        $ires = array_shift(explode('</div><!--z-->', array_pop(explode('id="ires">', $html))));

        // Split
        $chunkA = preg_split('~<div class="g"><!--m-->|<!--n--></div>~', $ires);

        // Foreach chunk check whether contained html, is related to one of organic results
        foreach ($chunkA as $idx => $chunk)
            if (!preg_match('~<div class="rc"><div class="r">~', $chunk))
                unset($chunkA[$idx]);

        // Reset keys
        $chunkA = array_values($chunkA);

        // Foreach
        foreach ($chunkA as $idx => $chunk) {

            // Capture data
            preg_match('~<div class="rc"><div class="r">'
                . '<a href="([^"]+)" ping="([^"]+)"><h3 class="[^"]+">([^<]+)</h3>'
                . '<br><div class="[^"]+"><cite class="[^"]+">([^<]+)</cite></div></a>~', $chunk, $m);

            //
            $results['organic'] []= [
                //'rank' => $idx + 1,
                //'position' => $idx + 1,
                'url' => $m[1],
                'display_url' => $m[4],
                'title' => $m[3],
                'description' => strip_tags(array_shift(explode('</span>', array_pop(explode('<div class="s"><div><span class="st">', $chunk)))))
            ];
        }

        // Get <g-section-with-header> elements' inner html
        $gsectA = [];
        foreach (preg_split('~<g-section-with-header[^>]+>~', $html) as $i => $_)
            if ($i) $gsectA []= array_shift(explode('</g-section-with-header>', $_));


        // Foreach found items
        foreach ($gsectA as $gsect) {

            // Detect <g-section-with-header> element's kind
            if (preg_match('~<div class="[^"]+"><h3 aria-level="2" role="heading">(Top stories|Videos)</h3>~', $gsect, $m)) {
                $kind = $m[1] == 'Top stories' ? 'top_stories' : 'videos';
            } else if (preg_match('~<h3 class="[^"]+" aria-level="2" role="heading" style="text-align:left">Searches related to [^<]+</h3>~', $gsect, $m)) {
                $kind = 'related';
            }

            // If kind is 'videos'
            if ($kind == 'videos') {

                // Get cards
                $cardA = [];
                foreach (preg_split('~<g-inner-card[^>]+>~', $gsect) as $i => $_)
                    if ($i) $cardA []= array_shift(explode('</g-inner-card>', $_));

                // Foreach card
                foreach($cardA as $card) {

                    // Pick card props
                    preg_match('~^<div class="[^"]+"><a href="([^"]+)"~', $card, $m);
                    preg_match('~aria-level="3" role="heading" style="[^"]+">([^<]+)</div></div></a><div class="[^"]+"><div class="[^"]+" style="[^"]+">([^<]+)</div></div><div class="[^"]+"><div class="[^"]+" style="[^"]+"><span class="[^"]+" style="[^"]+">([^<]+)</span> - [a-zA-Z]{3} [0-9]{1,2}, [0-9]{4}</div></div></div>~', $card, $m2);

                    // Assign
                    $results[$kind] []= [
                        'url' => $m[1],
                        'title' => $m2[1],
                        'description' => strip_tags($m2[2]),
                        'display_url' => $m2[3]
                    ];
                }

            // Else if kind is 'related'
            } else if ($kind == 'related') {

                // Pick slices
                $itemA = [];
                foreach (preg_split('~<p class="[^"]+"><a href="/search\?q=[^"]+">~', $gsect) as $i => $_)
                    if ($i) $itemA []= array_shift(explode('</a></p>', $_));

                // Foreach slice
                foreach ($itemA as $itemI)

                    // Assign to results
                    $results[$kind] []= [
                        'title' => strip_tags($itemI),
                    ];
            }
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