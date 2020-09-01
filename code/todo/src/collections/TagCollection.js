import {BaseCollection} from './BaseCollection.js';

class TagCollection extends BaseCollection {
  constructor(store) {
    super(store);
    this.uri = '/api/tags/';
  }
}

export {TagCollection};
