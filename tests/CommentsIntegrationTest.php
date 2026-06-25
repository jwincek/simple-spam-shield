<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Simple_Spam_Shield\Core\Config;
use Simple_Spam_Shield\Core\Guard_Runner;
use Simple_Spam_Shield\Integrations\Comments;

/**
 * Integration test: a comment submission flows through the real guard
 * pipeline (Comments -> Guard_Runner -> the configured guards) and a
 * blocked submission is routed to the spam queue rather than discarded.
 *
 * Uses the actual config/guards.json definitions; only WordPress's own
 * functions are stubbed (see tests/bootstrap.php).
 */
final class CommentsIntegrationTest extends TestCase {

	private const SECRET = 'integration-test-secret-key-at-least-64-characters-0123456789abcd';

	public static function setUpBeforeClass(): void {
		// Load the real guard definitions and register the pipeline once
		// (Guard_Runner::init() appends, so it must not run per-test).
		Config::init( SIMPLE_SPAM_SHIELD_PLUGIN_ROOT . '/config/' );
		Guard_Runner::init();
	}

	protected function setUp(): void {
		$GLOBALS['simple_spam_shield_test_options']    = [
			'simple_spam_shield_enabled'          => true,
			'simple_spam_shield_protect_comments' => true,
			'simple_spam_shield_log_blocked'      => false, // no DB writes under unit tests
			'simple_spam_shield_token_secret'     => self::SECRET,
		];
		$GLOBALS['simple_spam_shield_test_transients'] = [];
		$GLOBALS['simple_spam_shield_test_caps']       = [];
		$_SERVER['REMOTE_ADDR']                        = '198.51.100.23';
		$_POST                                         = [];

		// Reset the integration's per-request "flagged" state.
		( new ReflectionProperty( Comments::class, 'flagged' ) )->setValue( null, false );
	}

	private function valid_token(): string {
		$issued = time() - 30;
		return $issued . '.' . hash_hmac( 'sha256', (string) $issued, self::SECRET );
	}

	private function commentdata(): array {
		return [
			'comment_content'      => 'A genuinely nice and helpful comment.',
			'comment_author'       => 'Alice',
			'comment_author_email' => 'alice@example.com',
		];
	}

	public function test_clean_submission_is_not_routed_to_spam(): void {
		$_POST['simple_spam_shield_website_url'] = '';
		$_POST['simple_spam_shield_form_loaded'] = $this->valid_token();

		$data = Comments::check_comment( $this->commentdata() );

		// Returned unchanged and left with its original approval status.
		$this->assertSame( 'A genuinely nice and helpful comment.', $data['comment_content'] );
		$this->assertSame( 1, Comments::maybe_mark_spam( 1, $data ) );
	}

	public function test_honeypot_hit_is_routed_to_the_spam_queue(): void {
		$_POST['simple_spam_shield_website_url'] = 'http://spam.example'; // bot filled the trap
		$_POST['simple_spam_shield_form_loaded'] = $this->valid_token();

		$data = Comments::check_comment( $this->commentdata() );

		$this->assertSame( 'spam', Comments::maybe_mark_spam( 1, $data ) );
	}

	public function test_forged_token_is_routed_to_the_spam_queue(): void {
		$_POST['simple_spam_shield_website_url'] = '';
		$_POST['simple_spam_shield_form_loaded'] = '123.deadbeef'; // bad signature

		Comments::check_comment( $this->commentdata() );

		$this->assertSame( 'spam', Comments::maybe_mark_spam( 1, [] ) );
	}

	public function test_moderator_submissions_bypass_the_pipeline(): void {
		$GLOBALS['simple_spam_shield_test_caps']['moderate_comments'] = true;
		$_POST['simple_spam_shield_website_url']                      = 'http://spam.example';

		$data = Comments::check_comment( $this->commentdata() );

		// Not flagged, despite the honeypot being filled.
		$this->assertSame( 1, Comments::maybe_mark_spam( 1, $data ) );
	}

	public function test_hard_block_mode_rejects_instead_of_routing(): void {
		$GLOBALS['simple_spam_shield_test_options']['simple_spam_shield_hard_block'] = true;
		$_POST['simple_spam_shield_website_url']                                     = 'http://spam.example';
		$_POST['simple_spam_shield_form_loaded']                                     = $this->valid_token();

		// The wp_die() stub throws so we can assert the hard-block path.
		$this->expectException( \RuntimeException::class );
		Comments::check_comment( $this->commentdata() );
	}
}
