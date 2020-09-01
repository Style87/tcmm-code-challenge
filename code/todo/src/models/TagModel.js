import {BaseModel} from './BaseModel.js';

class TagModel extends BaseModel {
  constructor() {
    super();
    this.uri = '/api/tags/';
    this.id = null;
    this.tag_name = '';
  }

  getUri() {
    return this.uri += this.id;
  }

  post(params, callback) {
    let data = {
      tag_name: this.tag_name
    };
    super.post(data, params, callback);
  }
}

export {TagModel};
