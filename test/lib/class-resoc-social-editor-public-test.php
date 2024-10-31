<?php
require_once dirname( __FILE__ ) . '/../class-resoc-social-editor-unit-test.php';
require_once dirname( __FILE__ ) . '/../../includes/lib/class-resoc-social-editor-public.php';

class Resoc_Social_Editor_Public_Test extends Resoc_Social_Editor_Test_Case {

  public function test_replace_placeholder() {
    // Placeholder on the first line
    $html_code = <<<EOL
<meta property="og:title" content="PLACEHOLDER">
<meta property="og:description" content="Some description">
<meta property="og:url" content="http://example.com">
EOL;
    $expected_html_code = <<<EOL
<meta property="og:title" content="A new value">
<meta property="og:description" content="Some description">
<meta property="og:url" content="http://example.com">
EOL;
    $this->assertEquals( $expected_html_code,
      Resoc_Social_Editor_Public::replace_placeholder(
        $html_code, 'PLACEHOLDER', 'A new value' ) );

    // Placeholder on a middle line
    $html_code = <<<EOL
<meta property="og:title" content="Some title">
<meta property="og:description" content="PLACEHOLDER">
<meta property="og:url" content="http://example.com">
EOL;
    $expected_html_code = <<<EOL
<meta property="og:title" content="Some title">
<meta property="og:description" content="Look at this description">
<meta property="og:url" content="http://example.com">
EOL;
    $this->assertEquals( $expected_html_code,
      Resoc_Social_Editor_Public::replace_placeholder(
        $html_code, 'PLACEHOLDER', 'Look at this description' ) );

    // Placeholder on the last line
    $html_code = <<<EOL
<meta property="og:title" content="Some title">
<meta property="og:description" content="Some description">
<meta property="og:url" content="PLACEHOLDER">
EOL;
    $expected_html_code = <<<EOL
<meta property="og:title" content="Some title">
<meta property="og:description" content="Some description">
<meta property="og:url" content="http://someurl.com">
EOL;
    $this->assertEquals( $expected_html_code,
      Resoc_Social_Editor_Public::replace_placeholder(
        $html_code, 'PLACEHOLDER', 'http://someurl.com' ) );

    // No value
    $html_code = <<<EOL
<meta property="og:title" content="PLACEHOLDER">
<meta property="og:description" content="Some description">
<meta property="og:url" content="http://example.com">
EOL;
    $expected_html_code = <<<EOL
<meta property="og:description" content="Some description">
<meta property="og:url" content="http://example.com">
EOL;
    $this->assertEquals( $expected_html_code,
      Resoc_Social_Editor_Public::replace_placeholder(
        $html_code, 'PLACEHOLDER', NULL ) );

    // Array
    $html_code = <<<EOL
<meta property="og:title" content="Some title">
<meta property="og:description" content="PLACEHOLDER">
<meta property="og:url" content="http://example.com">
EOL;
    $expected_html_code = <<<EOL
<meta property="og:title" content="Some title">
<meta property="og:description" content="D1">
<meta property="og:description" content="D2">
<meta property="og:description" content="D3">
<meta property="og:url" content="http://example.com">
EOL;
    $this->assertEquals( $expected_html_code,
      Resoc_Social_Editor_Public::replace_placeholder(
        $html_code, 'PLACEHOLDER', array( 'D1', 'D2', 'D3' ) ) );

  // Nothing to replace
  $html_code = <<<EOL
<meta property="og:title" content="Somze title">
<meta property="og:description" content="Some description">
<meta property="og:url" content="http://example.com">
EOL;
  $this->assertEquals( $html_code,
    Resoc_Social_Editor_Public::replace_placeholder(
      $html_code, 'PLACEHOLDER', 'A new value' ) );


    // Placeholder is a date
    $html_code = <<<EOL
<meta property="og:title" content="2016-10-13T15:44:04+0000">
<meta property="og:description" content="Some description">
<meta property="og:url" content="http://example.com">
EOL;
    $expected_html_code = <<<EOL
<meta property="og:title" content="A new value">
<meta property="og:description" content="Some description">
<meta property="og:url" content="http://example.com">
EOL;
    $this->assertEquals( $expected_html_code,
      Resoc_Social_Editor_Public::replace_placeholder(
        $html_code, '2016-10-13T15:44:04+0000', 'A new value' ) );
  }

}
