class BaseModel {
  constructor() {
    this.uri = '';
  }

  postUri() {
    return this.uri;
  }

  withQuery(url, params) {
    let query = Object.keys(params)
      .filter(k => params[k] !== undefined)
      .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
      .join('&');
    if (query !== '') {
      url += (url.indexOf('?') === -1 ? '?' : '&') + query;
    }
    return url;
  }

  post(data, params, callback) {
    fetch(this.withQuery(this.postUri(), params), {
      method: 'post',
      body: JSON.stringify(data),
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
      }
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
      callback(data);
    })
  }

  delete(params, callback) {
    fetch(this.withQuery(this.getUri(), params), {
      method: 'delete',
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
      }
    })
    .then(response => {
      if (response.ok) {
        return null;
      } else {
        const text = response.text();
        throw new Error(text);
      }
    })
    .then(data => {
      callback(data);
    })
  }
}

export {BaseModel};
