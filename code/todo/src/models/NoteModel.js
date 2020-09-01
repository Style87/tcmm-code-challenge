import {BaseModel} from './BaseModel.js';

class NoteModel extends BaseModel {
  constructor() {
    super();
    this.uri = '/api/notes/';
    this.id = null;
    this.title = '';
    this.description = '';
    this.created_timestamp = '';
    this.Tags = [];
  }

  getUri() {
    return this.uri += this.id;
  }

  post(params, callback) {
    let data = {
      title: this.title,
      description: this.description,
      tag_ids: []
    };

    for (var i=0; i<this.Tags.length; i++) {
       data.tag_ids.push(this.Tags[i].id);
    }

    super.post(data, params, callback);
  }
}

export {NoteModel};
