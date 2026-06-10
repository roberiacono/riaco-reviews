import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    RangeControl,
    SelectControl,
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
    const {
        count,
        layout,
        showAuthorName,
        showAvatar,
        showDate,
        showRating,
        showSource,
        showTag,
        orderby,
        order,
    } = attributes;

    const blockProps = useBlockProps( {
        className: 'riaco-reviews-editor-preview',
    } );

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
                </PanelBody>

                <PanelBody title={ __( 'Field Visibility', 'riaco-reviews' ) } initialOpen={ true }>
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
            </InspectorControls>

            <div { ...blockProps }>
                <span className="riaco-reviews-editor-preview__icon dashicons dashicons-star-filled"></span>
                <p className="riaco-reviews-editor-preview__label">
                    { __( 'RIACO Reviews', 'riaco-reviews' ) }
                </p>
                <p className="riaco-reviews-editor-preview__desc">
                    { count } { __( 'reviews', 'riaco-reviews' ) } &mdash; { layout } { __( 'layout', 'riaco-reviews' ) }
                </p>
                <p className="riaco-reviews-editor-preview__hint">
                    { __( 'Configure display options in the sidebar.', 'riaco-reviews' ) }
                </p>
            </div>
        </>
    );
}
