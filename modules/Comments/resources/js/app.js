
import CollapsableCommentsList from './components/CollapsableCommentsList.vue'
import CollapseableCommentsLink from './components/CollapseableCommentsLink.vue'
import CommentsAdd from './components/CommentsAdd.vue'
import CommentsList from './components/CommentsList.vue'
import CommentsStore from './store/Comments'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router, store) {
    app.component('CollapseableCommentsLink', CollapseableCommentsLink)
    app.component('CommentsAdd', CommentsAdd)
    app.component('CollapsableCommentsList', CollapsableCommentsList)
    app.component('CommentsList', CommentsList)

    store.registerModule('comments', CommentsStore)
  })
}
