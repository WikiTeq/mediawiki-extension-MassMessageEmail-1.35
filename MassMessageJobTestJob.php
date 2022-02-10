<?php

use MediaWiki\MassMessage\Job\MassMessageJob;
use MediaWiki\MediaWikiServices;
use Soundasleep\Html2Text;
use Soundasleep\Html2TextException;

/**
 * This inherits from MassMessageJob, as a hacky way to get access to its protected methods.
 */
class MassMessageJobTestJob extends MassMessageJob {

	/**
	 * Sends the email
	 *
	 * @global string $wgArticlePath
	 * @global string $wgServer
	 * @param MassMessageJob $massMessageJob
	 * @return bool
	 */
	public function sendMassMessageEmailTest( MassMessageJob $massMessageJob ) {
		global $wgArticlePath, $wgServer;

		$title = $massMessageJob->getTitle();
		// Generate plain text ...
		$status = $massMessageJob->makeText();
		if ( !$status->isGood() ) {
			// If the status isn't good, MassMessage will proceed to post to the user's page instead.
			return false;
		}
		$text = $status->getValue();
		// Make sure we don't send relative links in the email. Shouldn't that be a ParserOption?
		$oldArticlePath = $wgArticlePath;
		$wgArticlePath = $wgServer . $wgArticlePath;
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$parserOutput = $parser->parse( $text, $title, ParserOptions::newFromAnon() );
		// ... and also generate HTML from the wikitext, which makes sense since
		// we're sending an email, but it requires $wgAllowHTMLEmail
		$html = $parserOutput->getText( [
			'enableSectionEditLinks' => false
		] );
		try {
			$text = Html2Text::convert( $html, [ 'ignore_errors' => true, 'drop_links' => true ] );
		} catch ( Html2TextException $exception ) {
			wfDebugLog( 'MassMessageEmail',
				'Unable to convert HTML email version into text version, falling back to tags stripping' );
			$text = strip_tags( $html );
		}
		return $text;
	}
}
