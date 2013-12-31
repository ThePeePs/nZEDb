<?php

//This script will update all records in the movieinfo table

require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'movie.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$movie = new Movie(true);
$db = new Db();
$updated = $deleted = 0;
$c = new ColorCLI();

if ($argc == 1 || $argv[1] != 'true') {
    exit($c->error("\nThis script will check all images in covers/movies and compare to db->movieinfo.\nTo run:\nphp $argv[0] true\n"));
}

$dirItr = new RecursiveDirectoryIterator(nZEDb_ROOT . 'www/covers/movies/');
$itr = new RecursiveIteratorIterator($dirItr, RecursiveIteratorIterator::LEAVES_ONLY);
foreach ($itr as $filePath) {
    if (is_file($filePath) && preg_match('/-cover\.jpg/', $filePath)) {
        preg_match('/(\d+)-cover\.jpg/', basename($filePath), $match);
        if (isset($match[1])) {
            $run = $db->queryDirect("UPDATE movieinfo SET cover = 1 WHERE imdbid = " . $match[1]);
            if ($run->rowCount() > 0) {
                $updated++;
            } else {
                $run = $db->queryDirect("SELECT imdbid FROM movieinfo WHERE imdbid = " . $match[1]);
                if ($run->rowCount() == 0) {
                    echo $c->info($filePath . " not found in db.");
                }
            }
        }
    }
    if (is_file($filePath) && preg_match('/-backdrop\.jpg/', $filePath)) {
        preg_match('/(\d+)-backdrop\.jpg/', basename($filePath), $match1);
        if (isset($match1[1])) {
            $db->queryDirect("UPDATE movieinfo SET backdrop = 1 WHERE imdbid = " . $match1[1]);
            if ($run->rowCount() > 0) {
                $updated++;
            } else {
                $run = $db->queryDirect("SELECT imdbid FROM movieinfo WHERE imdbid = " . $match[1]);
                if ($run->rowCount() == 0) {
                    echo $c->info($filePath . " not found in db.");
                }
            }
        }
    }
}

$qry = $db->queryDirect("SELECT imdbid FROM movieinfo WHERE cover = 1");
foreach ($qry as $rows) {
    if (!is_file('/var/www/nZEDb/www/covers/movies/' . $rows['imdbid'] . '-cover.jpg')) {
        $db->queryDirect("UPDATE movieinfo SET cover = 0 WHERE imdbid = " . $rows['imdbid']);
        echo $c->info('/var/www/nZEDb/www/covers/movies/' . $rows['imdbid'] . "-cover.jpg = not found.");
        $deleted++;
    }
}
$qry1 = $db->queryDirect("SELECT imdbid FROM movieinfo WHERE backdrop = 1");
foreach ($qry1 as $rows) {
    if (!is_file('/var/www/nZEDb/www/covers/movies/' . $rows['imdbid'] . '-backdrop.jpg')) {
        $db->queryDirect("UPDATE movieinfo SET backdrop = 0 WHERE imdbid = " . $rows['imdbid']);
        echo $c->info('/var/www/nZEDb/www/covers/movies/' . $rows['imdbid'] . "-backdrop.jpg = not found.");
        $deleted++;
    }
}

echo $c->header($updated . " movies set.");
echo $c->header($deleted . " movies unset.");
