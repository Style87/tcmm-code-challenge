class BaseCollection {
  constructor(store) {
    this.uri = '';
    this.store = store;
    this.params = {};
  }

  prepend(data) {
    this.store.update((store) =>{
      store.unshift(data);
      return store;
    })
  }

  append(data) {
    this.store.update((store) =>{
      store.push(data);
      return store;
    })
  }

  setParams(params) {
    this.params = params;
  }

  getUri() {
    return this.uri;
  }

  withQuery(url, params) {
    let query = Object.keys(params)
      .filter(k => params[k] !== undefined)
      .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
      .join('&');
    url += (url.indexOf('?') === -1 ? '?' : '&') + query;
    return url;
  }

  get() {
    fetch(this.withQuery(this.getUri(), this.params), {
      method: 'GET',
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
    })
    .then(response => {
      if (response.ok) {
        return response.json();
      } else {
        const text = response.text();
        throw new Error(text);
      }
    })
    .then(data => {
      this.store.set(data);
    });
  }
}

export {BaseCollection};
