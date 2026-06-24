(() => {
const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;
const { createElement, useEffect } = window.wp.element;
const { __ } = window.wp.i18n;

// Retrieve settings injected from PHP
const settings = getSetting('al_etihad_cliq_data', {});
const defaultLabel = window.wp.htmlEntities.decodeEntities(settings.title) || 'Bank Al Etihad CliQ';
const defaultDescription = window.wp.htmlEntities.decodeEntities(settings.description) || 'Pay easily using your CliQ Alias.';

const Content = (props) => {
	const { eventRegistration, emitResponse } = props;
	const { onPaymentSetup } = eventRegistration;

	useEffect(() => {
		const unsubscribe = onPaymentSetup(() => {
			const cliqAliasInput = document.getElementById('al_etihad_cliq_alias');
			const cliqAlias = cliqAliasInput ? cliqAliasInput.value : '';
			
			if (!cliqAlias.trim()) {
				return {
					type: emitResponse.responseTypes.ERROR,
					message: __('Please enter your CliQ Alias.', 'al-etihad-cliq'),
				};
			}

			// Pass the alias so it gets intercepted by Store API
			return {
				type: emitResponse.responseTypes.SUCCESS,
				meta: {
					paymentMethodData: {
						al_etihad_cliq_alias: cliqAlias,
					},
				},
			};
		});

		return () => {
			unsubscribe();
		};
	}, [onPaymentSetup, emitResponse]);

	return createElement(
		'div',
		{ className: 'al-etihad-cliq-payment-block-fields', style: { padding: '16px', background: '#f7f7f7', borderRadius: '4px', marginTop: '10px' } },
		createElement('p', { style: { margin: '0 0 10px 0' } }, defaultDescription),
		createElement(
			'div',
			null,
			createElement('input', {
				type: 'text',
				id: 'al_etihad_cliq_alias',
				name: 'al_etihad_cliq_alias',
				placeholder: __('Your CliQ Alias', 'al-etihad-cliq'),
				required: true,
				style: { width: '100%', padding: '12px', border: '1px solid #ccc', borderRadius: '4px', fontSize: '14px', boxSizing: 'border-box' }
			})
		)
	);
};

const alEtihadCliqPaymentMethod = {
	name: 'al_etihad_cliq',
	label: createElement('span', null, defaultLabel),
	content: createElement(Content),
	edit: createElement(Content),
	canMakePayment: () => true,
	ariaLabel: defaultLabel,
	supports: {
		features: ['products'],
	},
};

registerPaymentMethod(alEtihadCliqPaymentMethod);
})();
