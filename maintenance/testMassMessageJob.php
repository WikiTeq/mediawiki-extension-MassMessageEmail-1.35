<?php

namespace MediaWiki\MassMessage;

use Maintenance;
use MassMessageJobTestJob;
use MediaWiki\MassMessage\Job\MassMessageJob;
use Title;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * Script to send MassMessages server-side
 *
 * Expects a page list formatted as a .tsv file, with "PageName<tab>WikiId" on each line.
 * Subject line and message body are also stored as files.
 */
class TestMassMessageJob extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption('title', 'subject title to parse', true, true);
		$this->addOption('delay', 'delay too wait until repeat', true, true);
	}

	public function execute() {
		$title = Title::newFromText( $this->getOption('title') );
		$delay = $this->getOption('delay');
		if( !$title || !$title->exists() ) {
			$this->fatalError('Title is invdalid or does not exists!');
		}
		$job = new MassMessageJob(
			$title,
			[
				'comment' => '',
				'message' => '',
				'pageMessageTitle' => $title
			]
		);
		$test = new MassMessageJobTestJob(
			$title,
			[]
		);
		while( true ) {
			$text = $test->sendMassMessageEmailTest( $job );
			if( strpos( $text, 'Error!' ) !== false ) {
				$this->fatalError('Error!');
			}
			$this->output( 'OK' );
			sleep( $delay );
		}
	}
}

$maintClass = TestMassMessageJob::class;
require_once RUN_MAINTENANCE_IF_MAIN;
