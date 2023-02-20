<?php
require_once realpath(__DIR__ . '/../vendor/autoload.php');

use Orhanerday\OpenAi\OpenAi;
//*
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../../');      // local version
//*/$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../../../etc/objectcoded.ca/');  // server version
$dotenv->load();

define("STREAM_DELIMIT", "\r\n");

$msgs = ['{"id": "cmpl-6dPfBC9pCJmHxU8MLxhkyP3DugMZJ", "object": "text_completion", "created": 1674851461, "choices": [{"text": " I", "index": 0, "logprobs": null, "finish_reason": null}], "model": "davinci"}',
'{"id": "cmpl-6dPfBC9pCJmHxU8MLxhkyP3DugMZJ", "object": "text_completion", "created": 1674851461, "choices": [{"text": " have", "index": 0, "logprobs": null, "finish_reason": null}], "model": "davinci"}',
'{"id": "cmpl-6dPfBC9pCJmHxU8MLxhkyP3DugMZJ", "object": "text_completion", "created": 1674851461, "choices": [{"text": " two", "index": 0, "logprobs": null, "finish_reason": null}], "model": "davinci"}',
'{"id": "cmpl-6dPfBC9pCJmHxU8MLxhkyP3DugMZJ", "object": "text_completion", "created": 1674851461, "choices": [{"text": " children", "index": 0, "logprobs": null, "finish_reason": null}], "model": "davinci"}',
'{"id": "cmpl-6dPfBC9pCJmHxU8MLxhkyP3DugMZJ", "object": "text_completion", "created": 1674851461, "choices": [{"text": ".", "index": 0, "logprobs": null, "finish_reason": null}], "model": "davinci"}',
'{"id": "cmpl-6dPfBC9pCJmHxU8MLxhkyP3DugMZJ", "object": "text_completion", "created": 1674851461, "choices": [{"text": " ", "index": 0, "logprobs": null, "finish_reason": null}], "model": "davinci"}',
'{"id": "cmpl-6dPfBC9pCJmHxU8MLxhkyP3DugMZJ", "object": "text_completion", "created": 1674851461, "choices": [{"text": "\n", "index": 0, "logprobs": null, "finish_reason": null}], "model": "davinci"}',
'{"id": "cmpl-6dPfBC9pCJmHxU8MLxhkyP3DugMZJ", "object": "text_completion", "created": 1674851461, "choices": [{"text": " ", "index": 0, "logprobs": null, "finish_reason": "stop"}], "model": "davinci"}',
'[[DONE]]'
];

header('Content-type: text/plain');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');        // Prevent nginx from buffering response and using gzip and such.


foreach($msgs as $data) {
    sleep(1);
    echo $data;
    ob_flush();
    flush();
}
//*/
