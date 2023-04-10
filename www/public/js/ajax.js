/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */


const baseUri = document.baseURI.replace(/\/public\/?$/, '');
const apiUri = baseUri.replace(/\/www\/?$/, '/api');



/**
 * @param {string} response
 */
const debugResponse = txt => {
    const $debug = document.createElement('div');
    $debug.classList.add('ajax-request-debug');
    $debug.innerHTML = txt;
    document.body.querySelector('main').prepend($debug);
};


/**
 * @param {string} url
 * @param {'GET'|'POST'|'PUT'|'DELETE'|'PATCH'|'OPTIONS'|'HEAD'} method
 * @param {*|FormData} data
 * @param {'text'|'json'} type
 * @param {'json'|'urlencoded'|'multipart'} contentType
 * @param {{[key: string]: string}} additionalHeaders
 */
const AJAX = (url, method, data={}, type='json', contentType='json', additionalHeaders={}) => {
    return new Promise((resolve, reject) => {
        if(!['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'].includes(method))
            reject('Unknown method');

        if(data === null)
            data = {};

        else if(typeof data !== 'object')
            reject('AJAX\'s data must be object type.');

        switch(contentType) {
            case 'json': contentType = 'application/json'; break;
            case 'urlencoded': contentType = 'application/x-www-form-urlencoded'; break;
            case 'multipart': contentType = 'multipart/form-data'; break;
        }

        const headers = new Headers();
        headers.append('Accept', 'application/json');
        headers.append('Content-Type', contentType);
        headers.append('Access-Control-Origin', '*');

        for(const hdr in additionalHeaders) {
            headers.append(hdr, additionalHeaders[hdr]);
        }

        const startsWithSlash = url[0] === '/';
        
        const apiStartUrl = '/api/';

        if(url.startsWith(apiStartUrl)) {
            url = apiUri + '/' + url.slice(apiStartUrl.length);
            
            if(typeof SESS_TOKEN !== 'undefined') {
                headers.append('X-Auth-Token', SESS_TOKEN);
            }
        }

        else if(startsWithSlash || !/^(http)?:?\/?\//.test(url)) {
            url = `${baseUri}${startsWithSlash?'':'/'}${url}`;
        }
        

        /**
         * @var {RequestInit} options
         */
        const options = {
            method: method,
            headers: headers,
            mode: 'cors'
        };

        if(['POST', 'PUT', 'PATCH'].includes(method)) {
            options.body = data;

            if(!(data instanceof FormData)) {
                try {
                    options.body = JSON.stringify(data);
                }
                catch(e) {
                    console.error('Failed to parse Form Request Data as JSON :', data, e);
                    reject(e);
                }
            }
        }


        return fetch(url, options)
            .then(async response => {
                if(response.status >= 400) {
                    return reject({
                        status: response.status,
                        statusText: response.statusText,
                        url: response.url,
                        headers: headers,
                        resHeaders: response.headers,
                        body: data,
                        response: response.text()
                    });
                }

                const txtResult = await response.text();

                if(type === 'json') {
                    try {
                        if(txtResult.length > 0) {
                            const data = JSON.parse(txtResult);

                            if('status' in data && data.status >= 400) {
                                return reject(data);
                            }

                            return data;
                        }
                        return {};
                    }
                    catch(e) {
                        debugResponse(txtResult);
                        throw new Error('Failed to parse response as JSON.');
                    }
                }

                return txtResult;
            })
            .then(resolve)
            .catch(reject);
    });
};

/**
 * @param {string} url
 * @param {'text'|'json'} type
 * @param {'json'|'urlencoded'|'multipart'} contentType
 * @param {{[key: string]: string}} additionalHeaders
 */
export const GET = (url, type='json', contentType='json', additionalHeaders={}) => AJAX(url, 'GET', {}, type, contentType, additionalHeaders);
/**
 * @param {string} url
 * @param {*|FormData} data
 * @param {'text'|'json'} type
 * @param {'json'|'urlencoded'|'multipart'} contentType
 * @param {{[key: string]: string}} additionalHeaders
 */
export const POST = (url, data={}, type='json', contentType='json', additionalHeaders={}) => AJAX(url, 'POST', data, type, contentType, additionalHeaders);
/**
 * @param {string} url
 * @param {*|FormData} data
 * @param {'text'|'json'} type
 * @param {'json'|'urlencoded'|'multipart'} contentType
 * @param {{[key: string]: string}} additionalHeaders
 */
export const PUT = (url, data={}, type='json', contentType='json', additionalHeaders={}) => AJAX(url, 'PUT', data, type, contentType, additionalHeaders);
/**
 * @param {string} url
 * @param {'text'|'json'} type
 * @param {'json'|'urlencoded'|'multipart'} contentType
 * @param {{[key: string]: string}} additionalHeaders
 */
export const DELETE = (url, type='json', contentType='json', additionalHeaders={}) => AJAX(url, 'DELETE', {}, type, contentType, additionalHeaders);
/**
 * @param {string} url
 * @param {*|FormData} data
 * @param {'text'|'json'} type
 * @param {'json'|'urlencoded'|'multipart'} contentType
 * @param {{[key: string]: string}} additionalHeaders
 */
export const PATCH = (url, data={}, type='json', contentType='json', additionalHeaders={}) => AJAX(url, 'PATCH', data, type, contentType, additionalHeaders);
/**
 * @param {string} url
 * @param {'text'|'json'} type
 * @param {'json'|'urlencoded'|'multipart'} contentType
 * @param {{[key: string]: string}} additionalHeaders
 */
export const OPTIONS = (url, type='json', contentType='json', additionalHeaders={}) => AJAX(url, 'OPTIONS', {}, type, contentType, additionalHeaders);
/**
 * @param {string} url
 * @param {'text'|'json'} type
 * @param {'json'|'urlencoded'|'multipart'} contentType
 * @param {{[key: string]: string}} additionalHeaders
 */
export const HEAD = (url, type='json', contentType='json', additionalHeaders={}) => AJAX(url, 'HEAD', {}, type, contentType, additionalHeaders);