<?php
if (empty($argv[1]))
{
	if (strpos($argv[0], ".php") !== false)
	{
		die("Syntax: php chocogen.php <version>\n");
	}
	else
	{
		die("Syntax: chocogen <version>\n");
	}
}

$name = basename(getcwd());
$version = $argv[1];

if (!is_file(".chocogen.json"))
{
	file_put_contents(".chocogen.json", json_encode([
		"path" => []
	], JSON_PRETTY_PRINT));
	die(".chocogen.json not found in working directory. Created it. Please populate the 'path' array.\n");
}
$config = json_decode(file_get_contents(".chocogen.json"), true);
if (empty($config["path"]))
{
	die("Please populate the 'path' array in .chocogen.json.\n");
}

mkdir("chocogen");

$nuspec = <<<EOC
<?xml version="1.0"?>
<package>
  <metadata>
    <id>$name</id>
    <version>$version</version>
    <description>Why is this a required field?</description>
    <authors>Why is this a required field?</authors>
EOC;
if (!empty($config["dependencies"]))
{
    $nuspec .= "\n    <dependencies>\n";
    foreach ($config["dependencies"] as $dep)
    {
	    $nuspec .= "      <dependency id=\"".$dep["id"]."\"";
	    if (array_key_exists("version", $dep))
	    {
	    	$nuspec .= " version=\"".$dep["version"]."\"";
	    }
	    $nuspec .= " />\n";
	}
    $nuspec .= "    </dependencies>\n";
}
$nuspec .= <<<'EOC'
  </metadata>
</package>
EOC;
file_put_contents("chocogen/$name.nuspec", $nuspec);

function getExt($file)
{
	$arr = explode(".", $file);
	if (count($arr) < 2)
	{
		return "";
	}
	return $arr[count($arr) - 1];
}

$abort = false;
$need_path = false;

foreach ($config["path"] as $path)
{
	$file = basename($path);
	$ext = getExt($file);

	if ($ext == "php")
	{
		if (!$need_path)
		{
			$need_path = true;
			mkdir("chocogen/path");
		}

		copy($path, "chocogen/".$file);

		file_put_contents("chocogen/path/".substr($file, 0, -4).".bat", <<<EOC
		@echo off
		SET pathDir=%~dp0
		php "%pathDir%..\\$file" %*
		EOC);

		// MingW
		file_put_contents("chocogen/path/".substr($file, 0, -4), <<<EOC
		#!/bin/bash
		pathDir=$(dirname "\$0")
		php "\$pathDir/../$file" "$@"
		EOC);
	}
	else if ($ext == "exe" || $ext == "txt" || $ext == "")
	{
		copy($path, "chocogen/".$file);
	}
	else
	{
		$abort = true;
		echo "Don't know how to deal with .$ext file. Aborting.\n";
		break;
	}
}

if (!$abort)
{
	if ($need_path)
	{
		file_put_contents("chocogen/chocolateyInstall.ps1", <<<'EOC'
		$installDir = "$(Split-Path -parent $MyInvocation.MyCommand.Definition)"

		Install-ChocolateyPath "$installDir\path"
		EOC);
	}

	chdir("chocogen");
	//passthru("choco pack");
	shell_exec("choco pack");
	chdir("..");

	foreach(scandir("chocogen") as $file)
	{
		if (substr($file, -6) == ".nupkg")
		{
			echo "Successfully created package $file\n";
			rename("chocogen/$file", $file);
			break;
		}
	}
}

function rmr($file)
{
	if(is_dir($file))
	{
		foreach(scandir($file) as $f)
		{
			if (substr($f, 0, 1) != ".")
			{
				rmr($file."/".$f);
			}
		}
		rmdir($file);
	}
	else
	{
		unlink($file);
	}
}

rmr("chocogen");
