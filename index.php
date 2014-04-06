<?php
	/*
		Grep v 2 © 2007-08 Nikola Smolenski <smolensk@eunet.yu>
				 © 2011 and 2014 Harry Burt <jarry1250@gmail.com>

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation; either version 2 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
	*/

	require_once( '/data/project/jarry-common/public_html/global.php' );

	$I18N->setDomain( 'grep' );

	$grepLang = ( isset( $_GET['lang'] ) && $_GET["lang"] != "" ) ? strtolower( $_GET['lang'] ) : $I18N->getLang();
	$grepNamespace = ( isset( $_GET['namespace'] ) && $_GET["namespace"] != "" ) ? intval( $_GET['namespace'] ) : 0;
	$grepPattern = ( isset( $_GET['pattern'] ) && $_GET["pattern"] != "" ) ? htmlspecialchars( $_GET['pattern'] ) : '';
	$grepProject = ( isset( $_GET['project'] ) && $_GET["project"] != "" ) ? strtolower( $_GET['project'] ) : 'wikipedia';
	$grepRedirects = ( isset( $_GET['redirects'] ) && $_GET['redirects'] == 'on' );
	$grepLimit = ( isset( $_GET['limit'] ) && $_GET['limit'] == 'on' );

	$namespaceNames = getNamespaces( $grepLang, $grepProject );
	$namespaceName = $namespaceNames[$grepNamespace];
	$namespaceSelect = getNamespaceSelect( $grepLang, $grepNamespace );

	if( !preg_match( '/^[a-z]{1,7}$/', $grepLang ) || !preg_match( '/^[0-9]{1,3}$/', $grepNamespace ) || !preg_match( '/^[a-z]{1,15}$/', $grepProject ) ){
		die( 'An unexpected error occurred' );
	}

	echo get_html( 'header', _html( 'title' ) );

?>
	<h3><?php echo _html( 'enter-details' ); ?></h3>
	<p><?php echo $I18N->msg( 'introduction', array( 'variables' => array( '<a href="http://' . $I18N->getLang() . '.wikipedia.org/wiki/' . str_replace( ' ', '_', $I18N->msg( 'regex' ) ) . '" target="_blank">' . $I18N->msg( 'explanation' ) . '</a>' ) ) ); ?></p>
	<form action="index.php" method="GET">
		<p><label for="lang"><?php echo _html( 'language-label' ) . _g( 'colon-separator' ); ?>&nbsp;</label><input
				type="text" name="lang" id="lang" value="<?php echo $grepLang; ?>" style="width:80px;" maxlength="7"
				required="required">.xxx.org<br/>
			<label for="project"><?php echo _html( 'project-label' ) . _g( 'colon-separator' ); ?>&nbsp;</label>xxx.<input
				type="text" name="project" id="project" value="<?php echo $grepProject; ?>" style="width:200px;" maxlength="20"
				required="required">.org<br/>
			<label for="namespace"><?php echo _html( 'namespace-label' ) . _g( 'colon-separator' ); ?>
				&nbsp;</label><?php echo $namespaceSelect; ?><br/>
			<label for="pattern"><?php echo _html( 'pattern-label' ) . _g( 'colon-separator' ); ?>&nbsp;</label>/<input
				type="text" name="pattern" id="pattern" style="width:200px;" value="<?php echo $grepPattern; ?>"
				required="required"/>/ <br/>
			<input type="checkbox" value="on"
				   name="redirects" id="redirects" <?php if( $grepRedirects ){
				echo ' checked="checked"';
			} ?>/>&nbsp;<label
				for="limit"><?php echo _html( 'redirects-label' ); ?></label><br/>
			<input type="checkbox" value="on" id="limit" name="limit"<?php if( $grepLimit ){
				echo ' checked="checked"';
			} ?>/>&nbsp;<label
				for="redirects"><?php echo _html( 'limit-label' ); ?></label><br/>
			<input type="submit" value="<?php echo _g( 'form-submit' ); ?>"/>&nbsp;
			<input type="reset" value="<?php echo _g( 'form-reset' ); ?>"/>
		</p>
	</form>
<?php
	if( isset( $_GET['pattern'] ) ){
		Counter::increment( 'grep/since6june2011.txt' );

		echo "<h3>" . _html( 'results' ) . "</h3>";

		$mysqli = dbconnect( get_databasename( $grepLang, $grepProject ) );
		$redir = ( $grepRedirects ) ? " AND page_is_redirect=0" : '';
		$grepPattern = $mysqli->real_escape_string( str_replace( " ", "_", $grepPattern ) );
		$res = $mysqli->query( "SELECT page_title, page_is_redirect FROM page WHERE page_namespace=$grepNamespace $redir AND page_title REGEXP '" . $grepPattern . "';" );
		if( $res->num_rows === 0 ){
			echo "<p>" . _html( 'error-zeroresults' ) . "</p>";
		} else {
			echo "<p>" . $I18n->msg( 'match-count', array( 'variables' => array( $res->num_rows ), 'parsemag' => true ) ) . "</p>";
			$limit = ( $grepLimit ) ? 100 : -1;
			$i = 0;
			echo "<ol>\n";
			while( $row = $res->fetch_assoc() ){
				echo "<li><a href=\"http://$grepLang.$grepProject.org/wiki/$namespaceName:" . $row['page_title'] . ( $row['page_is_redirect'] ? "?redirect=no" : "" ) . "\">" . str_replace( "_", " ", $row['page_title'] ) . "</a></li>\n";
				if( ++$i == $limit ){
					break;
				}
			}
			echo "</ol>\n";
		}
	}

	echo get_html( 'footer' );