/**
 * WooCommerce PayButton Blocks Integration JS
*/
(function( wc, wp ) {
    const { registerPaymentMethod } = wc.wcBlocksRegistry;
    const { getSetting } = wc.wcSettings;
    const { decodeEntities } = wp.htmlEntities;
    const { createElement } = wp.element;

    const settings = getSetting( 'paybutton_data', {} );
    
    const labelText = decodeEntities( settings.title || 'PayButton' );

    // Create a Custom Label Component (Image ONLY)
    const LabelIconOnly = () => {
        return createElement(
            'span', 
            { 
                style: { 
                    display: 'flex', 
                    alignItems: 'center', 
                    width: '100%' 
                } 
            },
            // 1. The Image
            settings.icon ? createElement( 'img', { 
                src: settings.icon, 
                alt: labelText,
                style: { 
                    maxHeight: '30px', // Slightly larger since it stands alone
                    objectFit: 'contain'
                } 
            } ) 
            // 2. Fallback: If no icon is found, show text so the button isn't invisible
            : createElement( 'span', null, labelText )
        );
    };

    const Content = () => {
        return createElement( 'div', null, decodeEntities( settings.description || '' ) );
    };

    registerPaymentMethod( {
        name: 'paybutton',
        label: createElement( LabelIconOnly ),
        content: createElement( Content ),
        edit: createElement( Content ),
        canMakePayment: () => true,
        ariaLabel: labelText,
        supports: {
            features: settings.supports,
        },
    } );
})( window.wc, window.wp );