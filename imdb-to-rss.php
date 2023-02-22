<?php

define('MAX_FILE_SIZE', 60000000);

include_once("simple_html_dom.php");


$imdbId = $_REQUEST["imdbId"];
$linkType = $_REQUEST["linkType"];


if(isset($imdbId) == false)
	die("imdb id not set");


$content = file_get_contents("https://www.imdb.com/title/{$imdbId}/episodes");

$doc = str_get_html($content);

$showTitle = trim($doc->find(".subpage_title_block__right-column a", 0)->plaintext);

if(isset($showTitle) == false)
	die("Show title not found, imdb id invalid or show no longer exists");


$rows = $doc->find("#episodes_content .eplist .list_item");

$rows = array_map(function ($row) 
{
	global $rows;
	global $showTitle;
	global $linkType;

	$TORRENT_SEARCH = "TORRENT_SEARCH";

	$season = null;
	$episode = null;

	$title = "{$showTitle} - " . trim($row->find(".info strong", 0)->plaintext);

	$image = $row->find(".image img", 0);
	$episodeIndexContent = trim($row->find(".image a div div", 0)->plaintext);

	$matches = Array();

	if(preg_match("/S(\d+), Ep(\d+)/", $episodeIndexContent, $matches) == 1)
	{
		if(count($matches) == 3)
		{
			$season = $matches[1];
			$episode = $matches[2];

			$title = "{$title} - Season {$matches[1]}, Episode {$matches[2]} of " . count($rows);
		}
	}

	$link = "https://www.imdb.com" . $row->find(".info strong a", 0)->href;

	if($linkType == $TORRENT_SEARCH)
	{
		$link = "https://1337x.to/search/" . $showTitle;
		
		if($season != null)
		{
			$link .= " s" . str_pad("" . $season, 2, "0", STR_PAD_LEFT) . "e" . str_pad("" . $season, 2, "0", STR_PAD_LEFT) . "/1/";
		}
	}

	$res = Array(
		"airdate" => trim($row->find(".airdate", 0)->plaintext),
		"description" => trim($row->find(".item_description", 0)->plaintext),
		"title" =>  $title,
		"link" => $link,
		"image" => $image != null ? $image->src : ""
	);

	return $res;
}, $rows);

$rows = array_filter($rows, function($row) 
{
	$airTimestamp = DateTime::createFromFormat("d M. Y", $row["airdate"])->getTimestamp();

	if($airTimestamp > time())
		return false;

	return true;
});


header("Content-Type: application/xml");

?>
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
	<channel>
		<title><?php echo $showTitle; ?> episode list</title>
		<link>https://www.imdb.com/title/<?php echo $imdbId; ?></link>
		<description>RSS feed of <?php echo $showTitle; ?> episode list</description>
		<?php

		foreach($rows as $row)
		{
			?>
				<item>
					<image>
						<url><?php echo $row["image"]; ?></url>
						<title><?php echo $row["title"]; ?></title>
  						<link><?php echo $row["link"]; ?></link>
					</image>
					<title><?php echo $row["title"]; ?></title>
					<link><?php echo $row["link"]; ?></link>
					<description><?php echo $row["description"]; ?></description>
				</item>
			<?php
		}
		?>

	</channel>
</rss>
<?php 


?>