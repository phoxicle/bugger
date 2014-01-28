#!/usr/bin/env php
<?php
require_once("../Autoloader.php");

$args = getopt("m:h:", ['guess','bugginess','help']);

if(isset($args['guess']))
{
    $risk = new Risk_Utils();

    if(isset($args['h']))
    {
        $risk->guess($args['h']);
    }
    else
    {
        $args['help'] = true;
    }
}

if(isset($args['bugginess']))
{
    $risk = new Risk_Utils();

    if(isset($args['h']))
    {
        $risk->bugginess($args['h']);
    }
    else
    {
        $args['help'] = true;
    }
}

//TODO update help
if(!array_filter($args) || isset($args['help']))
{
	$help = sprintf("
risk_utils - A number of interesting functions related to figuring out which commits cause bugs.

usage: risk_utils [guess] [bugginess] --hash a1a1a1 [help]

options:
    guess           Provided a fixing-commit hash, get the introducing commits. Hash does *not* need to be stored in
                    Risk database.

    bugginess       Provided a commit hash, get the total commit bugginess based on the bugs this commit may have caused.
                    The hash and bugs must *already be stored in Risk database*.

    help            Display this menu. Also see the blog post at http://blog.inside-box.net/?p=4063.
\n"
	);
	echo $help;
}