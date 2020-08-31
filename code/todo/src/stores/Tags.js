import { writable } from 'svelte/store';

function initialValue() {
  return {
    tags: [],
  }
}

function makeTagsStore(args) {
  // 1. Build the store and initialize it as empty and error free
  let initial = initialValue();
  let store = writable(initial, makeSubscribe(initial, args));
  return store;
}

function unsubscribe() {
  // Nothing to do in this case
}

function makeSubscribe(data, _args) {
  // 2. Create a closure with access to the
  // initial data and initialization arguments
  return set => {
    // 3. This won't get executed until the store has
    // its first subscriber. Kick off retrieval.
    fetchData(data, set);

    // 4. We're not waiting for the response.
    // Return the unsubscribe function which doesn't do
    // do anything here (but is part of the stores protocol).
    return unsubscribe;
  };
}

async function fetchData(data, set) {
  try {
    // 5. Dispatch the request for the tags
    const withQuery = (url, params) => {
      let query = Object.keys(params)
        .filter(k => params[k] !== undefined)
        .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
        .join('&');
      url += (url.indexOf('?') === -1 ? '?' : '&') + query;
      return url;
    };
    var params = {"omi":"Tags().order(tag_name,ASC)"};

    const response = await fetch(withQuery('/api/tags', params), {

      method: 'GET',
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
    });

    if(response.ok) {
      const tags = await response.json();
      data.tags = [];
      // 6. Extract the data we need and let observers know
      tags.forEach((tag) => {
        data.tags.push(tag);
      });
      set(data);

    } else {
      const text = response.text();
      throw new Error(text);
    }

  } catch(error) {
    // 6b. if there is a fetch error - deal with it
    // and let observers know
    data.error = error;
    set(data);
  }
}

const store = makeTagsStore();

export {store};
