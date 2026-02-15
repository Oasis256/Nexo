nsHooks.addFilter('http-client-url', 'ns-multistore', (url) => {
    const validURL = (str) => {
        // Handle empty or null strings
        if (!str || typeof str !== 'string') {
            return false;
        }

        // Create a more flexible pattern that handles missing protocols
        var pattern = new RegExp(
            '^' +
            '(?:(?:https?:\\/\\/)?)?' +  // optional protocol
            '(?:' +
                '(?:(?:[a-z\\d](?:[a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
                '(?:(?:\\d{1,3}\\.){3}\\d{1,3})|' + // OR ip (v4) address
                'localhost' + // OR localhost
            ')' +
            '(?::\\d+)?' + // optional port
            '(?:\\/[-a-z\\d%_.~+]*)*' + // optional path
            '(?:\\?[;&a-z\\d%_.~+=-]*)?' + // optional query string
            '(?:#[-a-z\\d_]*)?$', // optional fragment
            'i'
        );

        // Test the original string
        if (pattern.test(str)) {
            return true;
        }

        // If no protocol is present, try adding http:// and test again
        if (!/^https?:\/\//.test(str)) {
            const withProtocol = 'http://' + str;
            return pattern.test(withProtocol);
        }

        return false;
    }

    function urlinfo(inputUrl) {
        // 1) If the string looks like a relative path (e.g. "/api/notifications"), 
        //    treat it as path-only with no domain or extension.
        if (inputUrl.startsWith('/')) {
            return {
                domain: null,
                extension: null,
                query: '',
                secure: false,
                port: '',
                path: inputUrl
            };
        }

        try {
            // 2) Ensure the URL has a protocol or add one
            if (!/^(https?:\/\/|:\/\/)/.test(inputUrl)) {
                inputUrl = "http://" + inputUrl;
            } else if (inputUrl.startsWith("://")) {
                // If it starts with "://", assume "http://"
                inputUrl = "http" + inputUrl;
            }

            const url = new URL(inputUrl);

            const { hostname, protocol, search, port, pathname } = url;
            const secure = protocol === 'https:';

            // 3) Check if hostname is purely numeric with dots => IP
            const isIP = /^[0-9.]+$/.test(hostname);
            if (isIP) {
                return {
                    domain: hostname,
                    extension: null,
                    query: search || '',
                    secure,
                    port: port || '',
                    path: pathname
                };
            }

            // 4) Check if hostname is a single token (e.g. "localhost", "site")
            if (!hostname.includes('.')) {
                return {
                    domain: hostname,
                    extension: null,
                    query: search || '',
                    secure,
                    port: port || '',
                    path: pathname
                };
            }

            // 5) Otherwise, split to get domain + extension (e.g. "google" + "com")
            const domainParts = hostname.split('.');
            const extension = domainParts.pop();
            const domain = domainParts.join('.');

            return {
                domain: `${domain}.${extension}`, // e.g. "google.com"
                extension: extension,
                query: search || '',
                secure,
                port: port || '',
                path: pathname
            };

        } catch (error) {
            // 6) If parsing fails (e.g. weird input), fallback to path-only
            return {
                domain: null,
                extension: null,
                query: '',
                secure: false,
                port: '',
                path: inputUrl
            };
        }
    }

    const excluded = [
        '/sanctum/csrf-cookie',
        '/api/users/permissions'
    ];

    try {
        const urlDomain = urlinfo(url);
        const baseDomain = urlinfo(ns.base_url);
        const pureUrlDomain = `${urlDomain.domain}${urlDomain.port ? ':' + urlDomain.port : ''}`;
        const pureBaseDomain = `${baseDomain.domain}${baseDomain.port ? ':' + baseDomain.port : ''}`;

        if (pureUrlDomain === pureBaseDomain || urlDomain.domain === null) {

            /**
             * if the provided string is a URL
             * we believe it has been used with ns()->url( ... )
             * therefore, we don't need to adjust the URL.
             */
            if (!validURL(url) && !excluded.includes(url)) {
                const parts = url.split('/');
                const index = parts.indexOf('api');
                const multistore = parts.indexOf('store');

                /**
                 * We should only consider mutating the URL if
                 * the domain match the current domain defined on the value ns.base_url
                 */


                if (!ns.subDomainEnabled) {
                    /**
                     * if the url already included the "stores"
                     * segment we assume the url has previously been edited
                     * therefore, no need to update the URL.
                     */
                    if (multistore >= 0) {
                        return url;
                    }

                    /**
                     * We'll only add the segment store/ if the URL starts with /api
                     */
                    if (parts[1] === 'api') {
                        parts.splice(index + 1, 0, `store/${ns.storeID}`);
                    }

                }

                return parts.join('/');
            }
        }

        return url;
    } catch (exception) {
        console.error(exception);
        return url;
    }
});