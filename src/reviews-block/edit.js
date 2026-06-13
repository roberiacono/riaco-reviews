import { useBlockProps, InspectorControls, ColorPalette } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import {
    PanelBody,
    RangeControl,
    SelectControl,
    ToggleControl,
    BaseControl,
    Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
    const {
        count,
        layout,
        cardStyle,
        headingLevel,
        showAuthorName,
        showAvatar,
        showDate,
        showRating,
        showSource,
        showProduct,
        showTitle,
        showShadow,
        minWidth,
        orderby,
        order,
        productFilter,
        cardBg,
        cardTextColor,
        cardBorderColor,
        starColor,
        fontSize,
        lineHeight,
        productBg,
        productTextColor,
    } = attributes;

    const DEFAULTS = {
        count: 6, layout: 'grid', cardStyle: 'default', headingLevel: 3,
        showAuthorName: true, showAvatar: true, showDate: false, showRating: true,
        showSource: true, showProduct: true, showTitle: true, showShadow: true,
        minWidth: 280, orderby: 'date', order: 'DESC', productFilter: '',
        cardBg: '', cardTextColor: '', cardBorderColor: '', starColor: '',
        fontSize: '', lineHeight: '', productBg: '', productTextColor: '',
    };

    const blockProps = useBlockProps( { className: 'riaco-reviews-ssr-wrap' } );

    const availableProducts = ( window.riacoReviewsData?.products ) || [];
    const productOptions = [
        { label: __( '— All Products —', 'riaco-reviews' ), value: '' },
        ...availableProducts.map( ( t ) => ( { label: t.name, value: t.slug } ) ),
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Display Settings', 'riaco-reviews' ) } initialOpen={ true }>
                    <RangeControl
                        __next40pxDefaultSize
                        label={ __( 'Number of Reviews', 'riaco-reviews' ) }
                        value={ count }
                        onChange={ ( value ) => setAttributes( { count: value } ) }
                        min={ 1 }
                        max={ 50 }
                    />
                    <SelectControl
                        __next40pxDefaultSize
                        label={ __( 'Filter by Product', 'riaco-reviews' ) }
                        value={ productFilter }
                        options={ productOptions }
                        onChange={ ( value ) => setAttributes( { productFilter: value } ) }
                        help={ availableProducts.length === 0
                            ? __( 'No products found. Create products under Reviews → Products.', 'riaco-reviews' )
                            : undefined
                        }
                    />
                    <SelectControl
                        __next40pxDefaultSize
                        label={ __( 'Layout', 'riaco-reviews' ) }
                        value={ layout }
                        options={ [
                            { label: __( 'Grid',    'riaco-reviews' ), value: 'grid'    },
                            { label: __( 'Masonry', 'riaco-reviews' ), value: 'masonry' },
                        ] }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />
                    <SelectControl
                        __next40pxDefaultSize
                        label={ __( 'Card Style', 'riaco-reviews' ) }
                        value={ cardStyle }
                        options={ [
                            { label: __( 'Default', 'riaco-reviews' ), value: 'default' },
                            { label: __( 'Modern',  'riaco-reviews' ), value: 'modern'  },
                            { label: __( 'Minimal', 'riaco-reviews' ), value: 'minimal' },
                        ] }
                        onChange={ ( value ) => setAttributes( { cardStyle: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Card Shadow', 'riaco-reviews' ) }
                        checked={ showShadow }
                        onChange={ ( value ) => setAttributes( { showShadow: value } ) }
                    />
                    <RangeControl
                        __next40pxDefaultSize
                        label={ __( 'Min Card Width (px)', 'riaco-reviews' ) }
                        help={ __( 'Minimum column width. More columns appear as the container widens.', 'riaco-reviews' ) }
                        value={ minWidth }
                        onChange={ ( value ) => setAttributes( { minWidth: value } ) }
                        min={ 180 }
                        max={ 600 }
                        step={ 10 }
                    />
                    <Button
                        variant="link"
                        isDestructive
                        onClick={ () => setAttributes( DEFAULTS ) }
                        style={ { marginTop: '0.5rem' } }
                    >
                        { __( 'Reset all settings to defaults', 'riaco-reviews' ) }
                    </Button>
                </PanelBody>

                <PanelBody title={ __( 'Field Visibility', 'riaco-reviews' ) } initialOpen={ true }>
                    <ToggleControl
                        label={ __( 'Show Title', 'riaco-reviews' ) }
                        checked={ showTitle }
                        onChange={ ( value ) => setAttributes( { showTitle: value } ) }
                    />
                    { showTitle && (
                        <SelectControl
                            __next40pxDefaultSize
                            label={ __( 'Title Heading Level', 'riaco-reviews' ) }
                            value={ String( headingLevel ) }
                            options={ [
                                { label: 'H2', value: '2' },
                                { label: 'H3', value: '3' },
                                { label: 'H4', value: '4' },
                                { label: 'H5', value: '5' },
                                { label: 'H6', value: '6' },
                            ] }
                            onChange={ ( value ) => setAttributes( { headingLevel: parseInt( value, 10 ) } ) }
                        />
                    ) }
                    <ToggleControl
                        label={ __( 'Show Star Rating', 'riaco-reviews' ) }
                        checked={ showRating }
                        onChange={ ( value ) => setAttributes( { showRating: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Author Name', 'riaco-reviews' ) }
                        checked={ showAuthorName }
                        onChange={ ( value ) => setAttributes( { showAuthorName: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Avatar', 'riaco-reviews' ) }
                        checked={ showAvatar }
                        onChange={ ( value ) => setAttributes( { showAvatar: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Date', 'riaco-reviews' ) }
                        checked={ showDate }
                        onChange={ ( value ) => setAttributes( { showDate: value } ) }
                    />
                    { cardStyle !== 'minimal' && (
                        <ToggleControl
                            label={ __( 'Show Source Logo', 'riaco-reviews' ) }
                            checked={ showSource }
                            onChange={ ( value ) => setAttributes( { showSource: value } ) }
                        />
                    ) }
                    <ToggleControl
                        label={ __( 'Show Product', 'riaco-reviews' ) }
                        checked={ showProduct }
                        onChange={ ( value ) => setAttributes( { showProduct: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Sort Order', 'riaco-reviews' ) } initialOpen={ false }>
                    <SelectControl
                        __next40pxDefaultSize
                        label={ __( 'Order By', 'riaco-reviews' ) }
                        value={ orderby }
                        options={ [
                            { label: __( 'Date',   'riaco-reviews' ), value: 'date'   },
                            { label: __( 'Rating', 'riaco-reviews' ), value: 'rating' },
                            { label: __( 'Random', 'riaco-reviews' ), value: 'rand'   },
                        ] }
                        onChange={ ( value ) => setAttributes( { orderby: value } ) }
                    />
                    { orderby !== 'rand' && (
                        <SelectControl
                            __next40pxDefaultSize
                            label={ __( 'Direction', 'riaco-reviews' ) }
                            value={ order }
                            options={ [
                                { label: __( 'Newest First', 'riaco-reviews' ), value: 'DESC' },
                                { label: __( 'Oldest First', 'riaco-reviews' ), value: 'ASC'  },
                            ] }
                            onChange={ ( value ) => setAttributes( { order: value } ) }
                        />
                    ) }
                </PanelBody>

                <PanelBody title={ __( 'Card Colours', 'riaco-reviews' ) } initialOpen={ false }>
                    <BaseControl
                        __nextHasNoMarginBottom
                        label={ __( 'Card Background', 'riaco-reviews' ) }
                    >
                        <ColorPalette
                            value={ cardBg }
                            onChange={ ( val ) => setAttributes( { cardBg: val ?? '' } ) }
                        />
                    </BaseControl>
                    <BaseControl
                        __nextHasNoMarginBottom
                        label={ __( 'Card Text', 'riaco-reviews' ) }
                    >
                        <ColorPalette
                            value={ cardTextColor }
                            onChange={ ( val ) => setAttributes( { cardTextColor: val ?? '' } ) }
                        />
                    </BaseControl>
                    <BaseControl
                        __nextHasNoMarginBottom
                        label={ __( 'Card Border / Accent', 'riaco-reviews' ) }
                    >
                        <ColorPalette
                            value={ cardBorderColor }
                            onChange={ ( val ) => setAttributes( { cardBorderColor: val ?? '' } ) }
                        />
                    </BaseControl>
                    <BaseControl
                        __nextHasNoMarginBottom
                        label={ __( 'Star Rating', 'riaco-reviews' ) }
                    >
                        <ColorPalette
                            value={ starColor }
                            onChange={ ( val ) => setAttributes( { starColor: val ?? '' } ) }
                        />
                    </BaseControl>
                    <BaseControl
                        __nextHasNoMarginBottom
                        label={ __( 'Product Badge Background', 'riaco-reviews' ) }
                    >
                        <ColorPalette
                            value={ productBg }
                            onChange={ ( val ) => setAttributes( { productBg: val ?? '' } ) }
                        />
                    </BaseControl>
                    <BaseControl
                        __nextHasNoMarginBottom
                        label={ __( 'Product Badge Text', 'riaco-reviews' ) }
                    >
                        <ColorPalette
                            value={ productTextColor }
                            onChange={ ( val ) => setAttributes( { productTextColor: val ?? '' } ) }
                        />
                    </BaseControl>
                </PanelBody>

                <PanelBody title={ __( 'Typography', 'riaco-reviews' ) } initialOpen={ false }>
                    <RangeControl
                        __next40pxDefaultSize
                        label={ __( 'Review Text Size (rem)', 'riaco-reviews' ) }
                        value={ fontSize ? parseFloat( fontSize ) : 0.9375 }
                        onChange={ ( val ) => setAttributes( {
                            fontSize: ( val != null && val !== 0.9375 ) ? String( val ) : '',
                        } ) }
                        min={ 0.75 }
                        max={ 1.5 }
                        step={ 0.0625 }
                        allowReset
                        resetFallbackValue={ 0.9375 }
                    />
                    <RangeControl
                        __next40pxDefaultSize
                        label={ __( 'Line Height', 'riaco-reviews' ) }
                        value={ lineHeight ? parseFloat( lineHeight ) : 1.7 }
                        onChange={ ( val ) => setAttributes( {
                            lineHeight: ( val != null && val !== 1.7 ) ? String( val ) : '',
                        } ) }
                        min={ 1.2 }
                        max={ 2.5 }
                        step={ 0.1 }
                        allowReset
                        resetFallbackValue={ 1.7 }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                <ServerSideRender
                    block="riaco-reviews/reviews-block"
                    attributes={ attributes }
                />
            </div>
        </>
    );
}
