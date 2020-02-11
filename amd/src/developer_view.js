/**
 * @module     mod_observation/developer_view
 * @class      mod_observation
 * @package    developer_view
 */
define(['jquery', 'core/ajax', 'core/notification', 'mod_observation/Sortable',],
    function ($, ajax, notification, Sortable) {

    return {
        init: function () {
            alert('init\'d!');
        }
    };

});

// define(['jquery'], function($) {
//
//     /**
//      * Give me blue.
//      * @access private
//      * @return {string}
//      */
//     var makeItBlue = function() {
//         // We can use our jquery dependency here.
//         return $('.blue').show();
//     };
//
//     /**
//      * @constructor
//      * @alias module:block_overview/helloworld
//      */
//     var greeting = function() {
//         alert('howdayyy');
//
//         /** @access private */
//         var privateThoughts = 'I like the colour blue';
//
//         /** @access public */
//         this.publicThoughts = 'I like the colour orange';
//
//     };
//
//     /**
//      * A formal greeting.
//      * @access public
//      * @return {string}
//      */
//     greeting.prototype.formal = function() {
//         return 'How do you do?';
//     };
//
//     /**
//      * An informal greeting.
//      * @access public
//      * @return {string}
//      */
//     greeting.prototype.informal = function() {
//         return 'Wassup!';
//     };
//
//     return greeting;
// });