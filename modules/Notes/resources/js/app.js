
import NotesTab from './components/RecordTabNote.vue'
import NotesTabPanel from './components/RecordTabNotePanel.vue'
import RecordTabTimelineNote from './components/RecordTabTimelineNote.vue'

if (window.Innoclapps) {
  Innoclapps.booting(app => {
    app.component('NotesTab', NotesTab)
    app.component('NotesTabPanel', NotesTabPanel)
    app.component('RecordTabTimelineNote', RecordTabTimelineNote)
  })
}
