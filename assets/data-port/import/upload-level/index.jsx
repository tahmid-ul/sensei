/* global FormData */

import { FormFileUpload } from '@wordpress/components';
import { Notice } from '../../notice';
import { useReducer, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	startUploadAction,
	uploadFailureAction,
	uploadSuccessAction,
	uploadLevelReducer,
} from './data';

const initialLevels = [
	{
		key: 'courses',
		description: __( 'Courses CSV File', 'sensei-lms' ),
		isUploaded: false,
		inProgress: false,
		hasError: false,
		errorMsg: null,
		filename: null,
	},
	{
		key: 'lessons',
		description: __( 'Lessons CSV File', 'sensei-lms' ),
		isUploaded: false,
		inProgress: false,
		hasError: false,
		errorMsg: null,
		filename: null,
	},
	{
		key: 'questions',
		description: __( 'Questions CSV File', 'sensei-lms' ),
		isUploaded: false,
		inProgress: false,
		hasError: false,
		errorMsg: null,
		filename: null,
	},
];

/**
 * Helper method to upload a file.
 *
 * @param {FileList} files     The files of the input.
 * @param {string}   levelKey  The level key.
 * @param {Function} dispatch  Dispatch function to update state on success/failure.
 */
const uploadFile = ( files, levelKey, dispatch ) => {
	if ( files.length < 1 ) {
		return;
	}

	const file = files[ 0 ];

	if ( ! [ 'csv', 'txt' ].includes( file.name.split( '.' ).pop() ) ) {
		dispatch(
			uploadFailureAction(
				levelKey,
				__( 'Only CSV files are supported.', 'sensei-lms' )
			)
		);

		return;
	}

	dispatch( startUploadAction( levelKey, file.name ) );

	const data = new FormData();
	data.append( 'file', file );

	apiFetch( {
		path: `/sensei-internal/v1/import/file/${ levelKey }`,
		method: 'POST',
		body: data,
	} )
		.then( () => {
			dispatch( uploadSuccessAction( levelKey ) );
		} )
		.catch( ( error ) => {
			dispatch( uploadFailureAction( levelKey, error.message ) );
		} );
};

/**
 * Helper method which calculates if the files are ready to be imported.
 *
 * @param {Array}    levels  The array of the Levels.
 * @return {boolean}         True if the files are ready.
 */
const isReady = ( levels ) => {
	let hasUploaded = false;

	for ( const level of levels ) {
		if ( level.inProgress ) {
			return false;
		}

		hasUploaded = hasUploaded || level.isUploaded;
	}

	return hasUploaded;
};

/**
 * A component which displays a list of upload levels. Each level has each own description, upload button and a
 * placeholder for messages.
 *
 * @param {Object}   props
 * @param {Function} props.setReadyStatus  A callback which sets the state true if the levels are ready to be uploaded.
 * @param {Array}    props.initialState    The initial state of the levels.
 */
export const UploadLevels = ( {
	setReadyStatus,
	initialState = initialLevels,
} ) => {
	const [ levels, dispatch ] = useReducer( uploadLevelReducer, initialState );

	useEffect( () => setReadyStatus( isReady( levels ) ), [
		levels,
		setReadyStatus,
	] );

	const getLevelMessage = ( level ) => {
		if ( level.hasError ) {
			return <Notice message={ level.errorMsg } isError />;
		} else if ( level.isUploaded ) {
			return <Notice message={ level.filename } />;
		}
	};

	return (
		<ol>
			{ levels.map( ( level ) => {
				const message = getLevelMessage( level );

				return (
					<li
						key={ level.key }
						className={ 'sensei-upload-file-line' }
					>
						<p className={ 'sensei-upload-file-line__description' }>
							{ level.description }
						</p>
						<FormFileUpload
							accept={ [ '.csv', '.txt' ] }
							onChange={ ( event ) =>
								uploadFile(
									event.target.files,
									level.key,
									dispatch
								)
							}
						>
							{ level.inProgress
								? __( 'Uploading…', 'sensei-lms' )
								: __( 'Upload', 'sensei-lms' ) }
						</FormFileUpload>
						{ message }
					</li>
				);
			} ) }
		</ol>
	);
};