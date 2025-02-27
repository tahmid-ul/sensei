/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const { getContextByRole } = require( '../../../helpers/context' );
const PluginsPage = require( '../../../pages/admin/plugins/plugins' );

test.describe.serial( 'Setup Wizard', () => {
	test.use( { storageState: getContextByRole( 'admin' ) } );
	let pluginsPage;
	let page;
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		pluginsPage = new PluginsPage( page );
		await pluginsPage.goTo( 'admin.php?page=sensei_setup_wizard' );
	} );

	test( 'opens when first activating the Sensei LMS plugin', async () => {
		await pluginsPage.activatePlugin( 'sensei-lms', true );
		await expect( page.url() ).toMatch(
			'admin.php?page=sensei_setup_wizard'
		);
	} );

	test( 'shows a notice to run the Setup Wizard', async () => {
		await pluginsPage.goToPlugins();
		await page.locator( `text=Run the Setup Wizard` ).click();
		await expect( page.url() ).toMatch(
			'admin.php?page=sensei_setup_wizard'
		);
	} );

	test.describe.serial( 'Welcome step', () => {
		test( 'opens on first launch', async () => {
			await expect(
				page.locator( 'text=Welcome to Sensei LMS!' )
			).toHaveCount( 1 );
		} );

		test( 'displays usage tracking modal when clicking continue', async () => {
			await page.locator( `text=Continue` ).click();
			await expect(
				page.locator( 'text=Build a Better Sensei LMS' )
			).toHaveCount( 1 );
		} );

		test( 'marks welcome step done and goes to purpose step', async () => {
			await pluginsPage.closeUserTrackingModal();
			await expect(
				page.locator(
					'text=What is your primary purpose for offering online courses?'
				)
			).toHaveCount( 1 );
		} );
	} );

	test.describe.serial( 'Purpose step', () => {
		test.beforeAll( async () => {
			await pluginsPage.fillOutPurposeForm();
			await page.locator( 'text=Continue' ).click();
		} );

		test( 'marks purpose step done and goes to features step', async () => {
			await expect(
				page.locator(
					'text=Enhance your online courses with these optional features.'
				)
			).toHaveCount( 1 );
		} );
	} );

	test.describe.serial( 'Features step', () => {
		test.beforeAll( async () => {
			await pluginsPage.fillOutFeaturesForm();
			await page.locator( 'text=Continue' ).click();
		} );

		test( 'installs selected plugins', async () => {
			await page.locator( 'text=Install now' ).click();
			await expect(
				page.locator( 'text=Sensei LMS Certificates — Installed' )
			).toHaveCount( 1 );
			await page.locator( 'button' ).locator( 'text=Continue' ).click();
			await pluginsPage.goToPlugins();
			expect(
				await pluginsPage.isPluginActive( 'sensei-certificates' )
			).toBeTruthy();
		} );
	} );

	test.describe.serial( 'Ready step', () => {
		test.beforeEach( async () => {
			await pluginsPage.goTo( 'admin.php?page=sensei_setup_wizard' );
			await page.locator( 'text=Features' ).click();
			await pluginsPage.goToReadyStep();
		} );

		test( 'is available if it is the active step', async () => {
			await pluginsPage.stepIsComplete( page, 'Features' );
			await pluginsPage.stepIsActive( page, 'Ready' );
			await expect(
				page.locator(
					"text=You're ready to start creating online courses!"
				)
			).toHaveCount( 1 );
		} );
	} );
} );
