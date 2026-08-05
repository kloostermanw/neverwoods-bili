<?php

namespace Bili\Tests;

use Bili\Language;
use Bili\LanguageCollection;
use Bili\Tests\Support\CookieSpy;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    public const EN = "english-utf-8";
    public const NL = "nederlands-utf-8";

    /**
     * Tests getInstance.
     *
     * @return void
     */
    public function testGetInstance(): void
    {
        $objLanguage = Language::getInstance();
        $this->assertInstanceOf(Language::class, $objLanguage);
    }

    /**
     * Tests set lang.
     *
     * @return void
     */
    public function testSetLangNL(): void
    {
        $objLanguage = Language::getInstance();
        $objLanguage->setLang(self::NL);
        $this->assertEquals(self::NL, $objLanguage->getActiveLang());
    }

    /**
     * Tests set lang.
     *
     * @return void
     */
    public function testSetLangEN(): void
    {
        $objLanguage = Language::getInstance();
        $objLanguage->setLang(self::EN);
        $this->assertEquals(self::EN, $objLanguage->getActiveLang());
    }

    /**
     * Tests get active language.
     *
     * @return void
     */
    public function testGetActiveLang(): void
    {
        $objLanguage = Language::getInstance();
        $this->assertEquals(self::EN, $objLanguage->getActiveLang());
    }

    /**
     * Tests get translation in English.
     *
     * @return void
     */
    public function testGetEn(): void
    {
        Language::getInstance()->setLang(self::EN);
        $this->assertEquals("en", Language::get('abbr'));
        $this->assertEquals("Yes", Language::get('yes', 'message'));
    }

    /**
     * Tests get translation in Dutch.
     *
     * @return void
     */
    public function testGetNl(): void
    {
        Language::getInstance()->setLang(self::NL);
        $this->assertEquals("nl", Language::get('abbr'));
        $this->assertEquals("Ja", Language::get('yes', 'message'));
    }

    /**
     * Tests get translation in Dutch.
     *
     * @return void
     */
    public function testGetLangs(): void
    {
        $this->assertInstanceOf(LanguageCollection::class, Language::getInstance()->getLangs());

    }

    /**
     * setLang() with $blnPersist = false loads the language in-process but must
     * not write $_SESSION['language'] (stateless API use case, bili#29).
     *
     * @return void
     */
    public function testSetLangNoPersistDoesNotWriteSession(): void
    {
        // Normalize to a known active language, then clear the session so we
        // only observe writes caused by the switch under test.
        Language::getInstance()->setLang(self::EN);
        $_SESSION = [];

        $objLanguage = Language::getInstance();
        $objLanguage->setLang(self::NL, false);

        $this->assertEquals(self::NL, $objLanguage->getActiveLang());
        $this->assertArrayNotHasKey('language', $_SESSION);
    }

    /**
     * setLang() with $blnPersist = false must still load the language file so
     * subsequent Language::get() calls return strings in that language.
     *
     * @return void
     */
    public function testSetLangNoPersistStillLoadsLanguageFile(): void
    {
        Language::getInstance()->setLang(self::EN);
        Language::getInstance()->setLang(self::NL, false);

        $this->assertEquals('nl', Language::get('abbr'));
        $this->assertEquals('Ja', Language::get('yes', 'message'));
    }

    /**
     * The default ($blnPersist = true) preserves today's behavior: the chosen
     * language is written to $_SESSION['language'].
     *
     * @return void
     */
    public function testSetLangPersistWritesSession(): void
    {
        Language::getInstance()->setLang(self::EN);
        $_SESSION = [];
        Language::getInstance()->setLang(self::NL);

        $this->assertEquals(self::NL, $_SESSION['language']);
    }

    /**
     * The default ($blnPersist = true) writes the language cookie, scoped to the
     * root path with a 30-day expiry and the HttpOnly flag.
     *
     * @return void
     */
    public function testSetLangPersistWritesCookie(): void
    {
        Language::getInstance()->setLang(self::EN);
        CookieSpy::reset();

        Language::getInstance()->setLang(self::NL);

        $arrCalls = CookieSpy::calls();
        $this->assertCount(1, $arrCalls);

        [$strName, $strValue, $intExpires, $strPath, $strDomain, $blnSecure, $blnHttpOnly] = $arrCalls[0];
        $this->assertSame('language', $strName);
        $this->assertSame(self::NL, $strValue);
        $this->assertSame('/', $strPath);
        $this->assertSame('', $strDomain);
        $this->assertFalse($blnSecure);
        $this->assertTrue($blnHttpOnly);

        //*** Bracket the expiry rather than pin it, since time() advances.
        $this->assertGreaterThan(time() + 60 * 60 * 24 * 29, $intExpires);
        $this->assertLessThanOrEqual(time() + 60 * 60 * 24 * 30, $intExpires);
    }

    /**
     * setLang() with $blnPersist = false must not write the language cookie.
     *
     * @return void
     */
    public function testSetLangNoPersistDoesNotWriteCookie(): void
    {
        Language::getInstance()->setLang(self::EN);
        CookieSpy::reset();

        Language::getInstance()->setLang(self::NL, false);

        $this->assertSame([], CookieSpy::calls());
    }

    /**
     * An unknown language is never persisted, so an unvalidated Accept-Language
     * value cannot poison the stored choice.
     *
     * @return void
     */
    public function testSetLangUnknownLanguageIsNotPersisted(): void
    {
        Language::getInstance()->setLang(self::EN);
        $_SESSION = [];
        CookieSpy::reset();

        $this->assertFalse(Language::getInstance()->setLang('does-not-exist'));

        $this->assertArrayNotHasKey('language', $_SESSION);
        $this->assertSame([], CookieSpy::calls());
        $this->assertSame(self::EN, Language::getInstance()->getActiveLang());
    }

    /**
     * With $blnPersist = false a stored session language survives untouched.
     * A stateless call must not flip the visitor's saved choice.
     *
     * @return void
     */
    public function testSetLangNoPersistLeavesStoredSessionLanguageIntact(): void
    {
        Language::getInstance()->setLang(self::EN);
        $_SESSION['language'] = self::EN;

        Language::getInstance()->setLang(self::NL, false);

        $this->assertSame(self::EN, $_SESSION['language']);
        $this->assertSame(self::NL, Language::getInstance()->getActiveLang());
    }

    /**
     * setLang() reports whether the language file was actually loaded.
     * False means unchanged or unknown.
     *
     * @return void
     */
    public function testSetLangReturnsWhetherTheFileWasLoaded(): void
    {
        $objLanguage = Language::getInstance();

        //*** A fresh instance already holds the default, so nothing is reloaded.
        $this->assertFalse($objLanguage->setLang(self::EN));

        $this->assertTrue($objLanguage->setLang(self::NL));
        $this->assertTrue($objLanguage->setLang(self::EN, false));
        $this->assertFalse($objLanguage->setLang('does-not-exist'));
    }

    public function setUp(): void
    {
        parent::setUp();
        setlocale(LC_ALL, 'en_US.UTF-8');
        Language::setUseSecureCookie(false);
        $objLanguage = Language::singleton("english-utf-8", __DIR__ . '/languages/');
        $objLanguage->setLocale();
    }

    public function tearDown(): void
    {
        CookieSpy::reset();
        $_SESSION = [];
        $_COOKIE = [];

        parent::tearDown();
    }
}