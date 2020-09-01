import {BaseCollection} from './BaseCollection.js';
import { notesStore } from '../stores/Notes.js';

class NoteCollection extends BaseCollection {
  constructor(store) {
    super(store);
    this.uri = '/api/notes/';
  }
}

const noteCollection = new NoteCollection(notesStore);

export {noteCollection};
