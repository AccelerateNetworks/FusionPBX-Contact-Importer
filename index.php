<?php
/*
	GNU Public License
	Version: GPL 3
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//add multi-lingual support
$language = new text;
$text = $language->get();

//additional includes
require_once "resources/header.php";
require_once "resources/paging.php";
require_once __DIR__."/../billing/resources/utils.php";
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<b>Contact Importer</b>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<form method="post" action="import.php" enctype="multipart/form-data">
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td class="vncellreq" valign="top" align="left" nowrap="nowrap">File</td>
						<td class="vtable" valign="top" align="left" nowrap="nowrap">
							<input type="file" name="csv" />
						</td>
					</tr>
					<tr>
						<td class="vncellreq" valign="top" align="left" nowrap="nowrap"></td>
						<td class="vtable" valign="top" align="left" nowrap="nowrap">
							<input type="submit" value="Slurp Data" />
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
</table>
<?php
require_once "footer.php";
