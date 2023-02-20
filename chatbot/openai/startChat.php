<?php
// Initialization for the chat variables.
session_start();

require_once realpath(__DIR__ . '/../vendor/autoload.php');

define("EMBEDDINGS_FILE", 'text-embedding-ada-002_embeddings.csv');
//define("EMBEDDINGS_FILE", 'text-similarity-curie-001_embeddings.csv');
//define("EMBEDDINGS_FILE", 'text-search-curie-doc-001_embeddings.csv');
define("SECRET_PATH", __DIR__.'/../../../etc/objectcoded.ca/');
define("EMBEDDINGS_CSV", SECRET_PATH . EMBEDDINGS_FILE);

// Warm up GPT3 with sample responses we'd like to receive only on the first interaction.
$_SESSION["PREQ"] = "QUESTION: Give a short answer to the query '";
$_SESSION["POSTQ"] = "' drawn from the following options: ";
$_SESSION["PREA"] = "ANSWER: ";

$_SESSION['Interaction'] = $_SESSION["PREQ"] . "What is a dog?" . $_SESSION["POSTQ"] . 
                           $_SESSION["PREA"] . "A dog is an animal.\n";
unset($_SESSION['MessagesArray']);

// Load the text embeddings if not yet loaded.
function csvToArray($csvFile) {
    $file_to_read = fopen($csvFile, 'r');
    while (!feof($file_to_read)) {
        $lines[] = fgetcsv($file_to_read, 1000, ',');
    }
    fclose($file_to_read);
    return $lines;
}

$csv = csvToArray(EMBEDDINGS_CSV);

// Remove headers and empty last element.
array_shift($csv);
array_pop($csv);

// Convert the embeddings string to a float array and store in a session variable.
$_SESSION['EMBEDDINGS'] = array_map(function($arr) {
                            $embedding = explode(',', trim($arr[2],'[]'));
                            return [$arr[0], $arr[1], array_map('floatval', $embedding)];
                        }, $csv);

list($engine, $rest_file) = explode("_", EMBEDDINGS_FILE);
$_SESSION['EMBEDDING_ENGINE'] = $engine;
