<?php
session_start();

require_once realpath(__DIR__ . '/../vendor/autoload.php');
include "cosineSimilarity.php";

use Orhanerday\OpenAi\OpenAi;

define("STREAMING", false);
define("COMPLETION_ENGINE", "text-davinci-003");
define("EMBEDDING_ENGINE", $_SESSION["EMBEDDING_ENGINE"]);
define("SECRET_PATH", __DIR__.'/../../../etc/objectcoded.ca/');
$dotenv = Dotenv\Dotenv::createImmutable(SECRET_PATH);  
$dotenv->load();

$OPENAPI_KEY = $_ENV['OPENAI_API_KEY'];

$usrMsg = htmlspecialchars($_POST["mymsg"]);
//$usrMsg = "What services do you provide?";

$usrMsgCompletion = $_SESSION['PREQ'] . $usrMsg . $_SESSION['POSTQ'];

// GPT3 is stateless so it needs to be reminded of the interaction history.
$_SESSION['Interaction'] = $_SESSION['Interaction'] . $usrMsgCompletion . $_SESSION['PREA'];
$_SESSION['MessagesArray'][] = $_SESSION['PREQ'] . $usrMsg;

$openaiMsg = "";
$openaiRegx = "/\"text\": ?\"([^\"]+)/";

header('X-Accel-Buffering: no');        // Prevent nginx from buffering response and using gzip and such.
header('Content-Encoding: identity');
header('Content-type: text/plain');
header('Cache-Control: no-cache');

$open_ai = new OpenAi($OPENAPI_KEY);

// Stream the response.
$completion_options = [
    'model' => COMPLETION_ENGINE,
    'prompt' => $_SESSION['Interaction'],
    'temperature' => 0,
    'max_tokens' => 150,
    'frequency_penalty' => 0,
    'presence_penalty' => 0,
    'stop' => $_SESSION['PREQ'],
];

// Generate the query embedding:
$usr_embedding = $open_ai->embeddings([
    "model" => EMBEDDING_ENGINE,
    "input" => $usrMsg
]);

$decoded_embedding = json_decode($usr_embedding)->{'data'}[0]->{'embedding'};

// Get an array of cosine similarities.
$k_tops = 3;    // How many top results to return.
$i = 0;
$tops = ["max"=>array_fill(0, $k_tops, 0), "ind"=>array_fill(0, $k_tops, -1)];     // array of corresponding max values and their indices in the embeddings array.

$cosines = array_map(function($db_embedding) {
            global $tops, $decoded_embedding, $i, $k_tops;
            $save_max = -1;
            $save_ind = -1;
            $save_max2 = -1;
            $save_ind2 = -1;
            $sim = CosineSimilarity::dot_product($db_embedding[2], $decoded_embedding);
            for($j=0; $j<$k_tops; $j++)
            {
                // push all the values down by one
                if($sim > $tops["max"][$j]) {
                    $save_max = $tops["max"][$j];
                    $save_ind = $tops["ind"][$j];
                    $tops["max"][$j] = $sim;
                    $tops["ind"][$j] = $i;

                    for($k=$j+1; $k<$k_tops; $k++) {
                        // simple swap                        
                        $save_max2 = $tops["max"][$k];
                        $save_ind2 = $tops["ind"][$k];
                        $tops["max"][$k] = $save_max;
                        $tops["ind"][$k] = $save_ind;
                        $save_max = $save_max2;
                        $save_ind = $save_ind2;
                    }

                    break;
                }
            }

            $i++;
            return $sim;
}, $_SESSION['EMBEDDINGS']);

$_SESSION['Interaction'] = $_SESSION["PREQ"] . $usrMsg . $_SESSION["POSTQ"];

// Threshold the similarity to Thresh to accept results.
$sim_thresh = 0.75;
for($i=0; $i<$k_tops; $i++) {
    if ($tops["max"][$i] > $sim_thresh) {
        $_SESSION['Interaction'] .= $_SESSION['EMBEDDINGS'][$tops["ind"][$i]][1];

        if(STREAMING) {
            echo '{"option'.$i.'": "'.htmlspecialchars($_SESSION['EMBEDDINGS'][$tops["ind"][$i]][1]).'", "score": "'. $tops["max"][$i] .'"}';

            ob_flush();
            flush();
            usleep(10000);
        }
    }
    else {
        $_SESSION['Interaction'] .= "Sorry, this information cannot be found. Please rephrase your query.";

        if(STREAMING) {
            echo '{"option'.$i.'": "I couldn\'t find any relevant information about that.", "score": "'. $tops["max"][$i] .'"}';

            ob_flush();
            flush();
            usleep(10000);
        }

        break;
    }
}

$completion_options['prompt'] = $_SESSION['Interaction'] . $_SESSION["PREA"];

if(!STREAMING) {
    $gptCompletion = $open_ai->completion($completion_options);

    preg_match($openaiRegx, $gptCompletion, $textChunk);
    echo '{"text": "'.htmlspecialchars($textChunk[1]).'"}';

    $openaiMsg = $textChunk[1];

} else {
    $completion_options["stream"] = true;

    $gptCompletion = $open_ai->completion($completion_options, function ($curl_info, $data) {
        global $openaiMsg;
        global $openaiRegx;
        
        if(preg_match($openaiRegx, $data, $textChunk)) {
            $openaiMsg = $openaiMsg . str_replace('\n',"\n",$textChunk[1]); 

            echo '{"text": "'.htmlspecialchars($textChunk[1]).'"}';
            ob_flush();
            flush();
        }

        return strlen($data);
    });
}

// update the session interactions
// GPT3 is stateless so it needs to be reminded of the interaction history.
//$_SESSION['Interaction'] = $_SESSION['Interaction'] . $openaiMsg;
$_SESSION['MessagesArray'][] = $_SESSION['PREA'] . $openaiMsg;

/*
print( $_SESSION['Interaction']);
print("\n\n");
print_r ($_SESSION['MessagesArray']);

//*/
