#!/usr/bin/env php
<?php
require_once("../Autoloader.php");

$args = getopt("", ['retrieve','guess','bisect','bugginess','help']);

if(isset($args['retrieve']))
{
    $risk = new \Risk\Mine();
    $risk->retrieve();
}

if(isset($args['guess']))
{
    $risk = new \Risk\Mine();
    $risk->guess();
}

if(isset($args['bisect']))
{
    $risk = new \Risk\Mine();
    $risk->bisect();
}

if(isset($args['bugginess']))
{
    $risk = new \Risk\Mine();
    $risk->bugginess();
}

if(!array_filter($args) || isset($args['help']))
{
	$help = sprintf("
usage: risk_mine [--retrieve] [--guess] [--bisect] [--bugginess]

options:
    retrieve      Retrieve all commits and their tickets and store them in our database.
    guess         For every bug in our database, guess the bug-introducing commit by \Risk\External\Git DIFF.
    bisect        For every bug in our database, guess the bug-introducing commit via \Risk\External\Git-Bisect.
    bugginess     For each commit, calculate the bugginess score.

    help          Display this menu
\n"
	);
	echo $help;
}