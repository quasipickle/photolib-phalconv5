
/* global axios */
export async function get(url, formData, task, options) {
    return axiosWrapper(url, formData, task, "get", options);
}
export async function post(url, formData, task) {
    return axiosWrapper(url, formData, task, "post");
}


/**
 * A wrapper for POST/GETing via Axios that handles error reporting.
 *
 * If the result of the call doesn't have a "success" property: Handles error reporting if the request
 * wasn't successful, or if there was an error server-side.
 *
 * If the result of the call _does_ have a "success" property that's true: the response is cached.
 *
 * Returns false or the returned data
 *
 * @param {string}      url         The URL to POST to
 * @param {object}      formData    The data to POST
 * @param {string}      task        A string that describes the task being taken
 * @param {string}      verb        The verb to make the call with.  "get", "post"
 * @param {object}      options     An object of options.
 *                                      `fresh` - default:false.  Forces a new request, bypassing the cache.
 *                                                                Only GET requests are cached.
 * @returns
 */
async function axiosWrapper(url, formData, task, verb) {
    if (verb == "get" && Object.keys(formData).length > 0) {
        const options = {};
        if(formData instanceof FormData)
            options.params = JSON.parse(JSON.stringify(FormData));
        else
            options.params = formData;
        formData = options;
    }
    url += "?_=" + window.performance.now();
    const fn = axios[verb];
    const result = await fn(window.webRootPath + url, formData, {
        headers: {
            "Content-Type": "multipart/form-data"
        }
    })
        .then(response => {
            if (typeof response.data == "string") {
                alert(`When trying to ${task}, the server returned the message:\n\n${response.data}`);
                return false;
            } else if (typeof response.data != "object") {
                alert(`There was a communication error trying to ${task}.\n\nCheck the console.`);
                console.log(response);
                console.log(response.data);
                return false;
            } else if (typeof response.data.success === "undefined") {
                alert(`No success state was specified when trying to ${task}.`);
            } else if (response.data.success != true) {
                const message = Object.prototype.hasOwnProperty.call(response.data, "message")
                    ? response.data.message
                    : "Error message was not defined";
                alert(`There was a server error trying to ${task}:\n\n${message}.`);
                return false;
            } else {
                return response.data;
            }
        })
        .catch(error => {
            alert(`There was an unknown error trying to ${task}.\n\nCheck the console.`);
            console.log(error);
            return false;
        });
    return (!result) ? Promise.reject() : Promise.resolve(result);
}