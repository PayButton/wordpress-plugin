const settings = window.wc.wcSettings.getSetting( 'paybutton_data', {} );

const Label = ( props ) => {
    return wp.element.createElement(
        'span',
        { className: 'paybutton-label', style: { display: 'flex', alignItems: 'center' } },
        settings.icon ? wp.element.createElement('img', { 
            src: settings.icon, 
            alt: settings.title, 
            style: { height: '20px', marginRight: '8px' } 
        }) : null,
        settings.title
    );
};

const Content = () => {
    return wp.element.createElement(
        'div',
        null,
        wp.htmlEntities.decodeEntities( settings.description || 'Pay securely using PayButton. After placing your order, you’ll complete payment on the next page.' )
    );
};

const Block_Gateway = {
    name: 'paybutton',
    label: wp.element.createElement( Label, null ),
    content: wp.element.createElement( Content, null ),
    edit: wp.element.createElement( Content, null ),
    canMakePayment: () => true,
    ariaLabel: settings.title,
    supports: {
        features: settings.supports || [ 'products' ],
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );