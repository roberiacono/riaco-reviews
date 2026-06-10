import { useBlockProps, InspectorControls, ColorPalette } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import {
    PanelBody,
    RangeControl,
    SelectControl,
    ToggleControl,
    BaseControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
    const {
        count,
        layout,
        cardStyle,
        showAuthorName,
        showAvatar,
        showDate,
        showRating,
        showSource,
        showTag,
        showTitle,
        minWidth,
        orderby,
        order,
        cardBg,
        cardTextColor,
        cardBorderColor,
        starColor,
        fontSize,
        lineHeight,
        tagBg,
        tagTextColor,
    } = attributes;

    const blockProps = useBlockProps( { className: 'riaco-reviews-ssr-wrap' } );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Display Settings', 'riaco-reviews' ) } initialOpen={ true }>
                    <RangeControl
                        label={ __( 'Number of Reviews', 'riaco-reviews' ) }
                        value={ count }
                        onChange={ ( value ) => setAttributes( { count: value } ) }
                        min={ 1 }
                        max={ 50 }
                    />
                    <SelectControl
                        label={ __( 'Layout', 'riaco-reviews' ) }
                        value={ layout }
                        options={ [
                            { label: __( 'Grid',    'riaco-reviews' ), value: 'grid'    },
                            { label: __( 'Masonry', 'riaco-reviews' ), value: 'masonry' },
                        ] }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />
                    <SelectControl
                        label={ __( 'Card Style', 'riaco-reviews' ) }
                        value={ cardStyle }
                        options={ [
                            { label: __( 'Default', 'riaco-reviews' ), value: 'default' },
                            { label: __( 'Modern',  'riaco-reviews' ), value: 'modern'  },
                        ] }
                        onChange={ ( value ) => setAttributes( { cardStyle: value } ) }
                    />
                    <RangeControl
                        label={ __( 'Min Card Width (px)', 'riaco-reviews' ) }
                        value={ minWidth }
                        onChange={ ( value ) => setAttributes( { minWidth: value } ) }
                        min={ 180 }
                        max={ 600 }
                        step={ 10 }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Field Visibility', 'riaco-reviews' ) } initialOpen={ true }>
                    <ToggleControl
                        label={ __( 'Show Title', 'riaco-reviews' ) }
                        checked={ showTitle }
                        onChange={ ( value ) => setAttributes( { showTitle: value } ) }
                    />
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
                    <ToggleControl
                        label={ __( 'Show Source Logo', 'riaco-reviews' ) }
                        checked={ showSource }
                        onChange={ ( value ) => setAttributes( { showSource: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Tag', 'riaco-reviews' ) }
                        checked={ showTag }
                        onChange={ ( value ) => setAttributes( { showTag: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Sort Order', 'riaco-reviews' ) } initialOpen={ false }>
                    <SelectControl
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
                        label={ __( 'Tag Background', 'riaco-reviews' ) }
                    >
                        <ColorPalette
                            value={ tagBg }
                            onChange={ ( val ) => setAttributes( { tagBg: val ?? '' } ) }
                        />
                    </BaseControl>
                    <BaseControl
                        __nextHasNoMarginBottom
                        label={ __( 'Tag Text', 'riaco-reviews' ) }
                    >
                        <ColorPalette
                            value={ tagTextColor }
                            onChange={ ( val ) => setAttributes( { tagTextColor: val ?? '' } ) }
                        />
                    </BaseControl>
                </PanelBody>

                <PanelBody title={ __( 'Typography', 'riaco-reviews' ) } initialOpen={ false }>
                    <RangeControl
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
