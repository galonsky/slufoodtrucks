<?php

require 'tmhOAuth/tmhOAuth.php';
require 'tmhOAuth/tmhUtilities.php';
require 'auth.php';

$patterns = array(
    'slu',
    'amazon',
    'amzn',
    'boren',
    'harrison',
    'south[ ]*lake[ ]*union',
    'fairview',
    'republican'
);

$sinceId = NULL;
if ($id = file_get_contents('last_id.txt')) {
    $sinceId = $id;
}

$tmhOAuth = new tmhOAuth(array(
  'consumer_key'    => $CONSUMER_KEY,
  'consumer_secret' => $CONSUMER_SECRET,
  'user_token'      => $ACCESS_TOKEN,
  'user_secret'     => $ACCESS_SECRET,
));

$options = array(
  'screen_name' => 'FoodTruckCity',
  'trim_user'   => 'true',
  'count'       => 200
);
if (isset($sinceId)) {
    $options['since_id'] = $sinceId;
}

function patternize($word) {
    return "/" . $word . "/i";
}

function retweet($tweet) {
    global $tmhOAuth;
    echo "Retweeting tweet[" . $tweet['text'] . "]<br/>";
    return $tmhOAuth->request('POST', $tmhOAuth->url('1.1/statuses/retweet/' . $tweet['id_str'] . '.json'));
}

$tmhOAuth->request('GET', $tmhOAuth->url('1.1/statuses/user_timeline.json'), $options);

$tweets = json_decode($tmhOAuth->response['response'], true);
echo "Processing " . count($tweets) . " new tweets.<br/>";
//tmhUtilities::pr($tweets);
foreach ($tweets as $tweet) {
    if (array_key_exists('retweeted_status', $tweet)) {
        $tweet = $tweet['retweeted_status'];
        $text = $tweet['text'];
        foreach ($patterns as $word) {
            $accepted = false;
            $pattern = patternize($word);
            if (preg_match($pattern, $text) === 1) {
                echo 'ACCEPTED: ' . $text . '<br/>';
                $accepted = true;
                $code = retweet($tweet);
                if ($code != 200) {
                    echo "Could not retweet, code " . $code . "<br/>";
                }
                break;
            } 
        }
        if (!$accepted) {
            echo 'REJECTED: ' . $text . '<br/>';
        }
    }
}
if (count($tweets) > 0) {
    $last_id = $tweets[0]['id_str'];
    $fh = fopen("last_id.txt", 'w') or die("can't open file");
    fwrite($fh, $last_id);
}

?>