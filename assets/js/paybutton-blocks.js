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

    // Create a Custom Label Component (Dual Icons)
    const LabelIconOnly = () => {
        return createElement(
            'span', 
            { 
                style: { 
                    display: 'flex', 
                    alignItems: 'center', 
                    width: '100%',
                } 
            },
            // 1. The PayButton Image
            settings.icon ? createElement( 'img', { 
                src: settings.icon, 
                alt: labelText,
                style: { 
                    maxHeight: '30px', 
                    objectFit: 'contain'
                } 
            } ) : null,

            // 2. The Pipeline Separator (Only shows if BOTH icons exist)
            (settings.icon && settings.icon2) ? createElement( 'span', {
                style: {
                    margin: '0 10px', // Spacing around the pipe
                    color: '#ccc',    // Light gray color
                    fontSize: '24px', // Size of the pipe
                    lineHeight: '1',
                    fontWeight: '300'
                }
            }, '|' ) : null,

            // 3. The eCash Image
            settings.icon2 ? createElement( 'img', { 
                src: settings.icon2, 
                alt: 'eCash',
                style: { 
                    maxHeight: '24px', 
                    objectFit: 'contain'
                } 
            } ) : null,

            // 4. Fallback: If no icons are found, show text
            (!settings.icon && !settings.icon2) ? createElement( 'span', null, labelText ) : null
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