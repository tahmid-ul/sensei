import { RichText } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { getButtonProps, getButtonWrapperProps } from './button-props';
import { ButtonBlockSettings } from './settings-button';

/**
 * Edit component for a Button block.
 *
 * @param {Object} props
 */
export const EditButtonBlock = ( props ) => {
	const { placeholder, attributes, setAttributes, tagName } = props;
	const { text } = attributes;
	const { colors } = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getSettings();
	}, [] );

	const isReadonly = undefined !== props.text;
	const buttonProps = getButtonProps( { ...props, colors } );

	return (
		<div { ...getButtonWrapperProps( props ) }>
			{ isReadonly ? (
				<div { ...buttonProps }>{ props.text }</div>
			) : (
				<RichText
					placeholder={
						placeholder || __( 'Add text…', 'sensei-lms' )
					}
					value={ text }
					onChange={ ( value ) => setAttributes( { text: value } ) }
					withoutInteractiveFormatting
					{ ...buttonProps }
					tagName={ tagName }
					identifier="text"
				/>
			) }
			<ButtonBlockSettings { ...props } />
		</div>
	);
};