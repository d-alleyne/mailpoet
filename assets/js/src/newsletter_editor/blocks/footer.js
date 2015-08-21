/**
 * Footer content block
 */
define('newsletter_editor/blocks/footer', [
    'newsletter_editor/App',
    'backbone',
    'backbone.marionette',
    'mailpoet',
  ], function(EditorApplication, Backbone, Marionette, MailPoet) {

  EditorApplication.module("blocks.footer", function(Module, App, Backbone, Marionette, $, _) {
      "use strict";

      var base = App.module('blocks.base');

      Module.FooterBlockModel = base.BlockModel.extend({
          defaults: function() {
              return this._getDefaults({
                  type: 'footer',
                  text: '<a href="[unsubscribeUrl]">Unsubscribe</a> | <a href="[manageSubscriptionUrl]">Manage subscription</a><br /><b>Add your postal address here!</b>',
                  styles: {
                      block: {
                          backgroundColor: 'transparent',
                      },
                      text: {
                          fontColor: '#000000',
                          fontFamily: 'Arial',
                          fontSize: '12px',
                          textAlign: 'center',
                      },
                      link: {
                          fontColor: '#0000ff',
                          textDecoration: 'none',
                      },
                  },
              }, EditorApplication.getConfig().get('blockDefaults.footer'));
          },
      });

      Module.FooterBlockView = base.BlockView.extend({
          className: "mailpoet_block mailpoet_footer_block mailpoet_droppable_block",
          getTemplate: function() { return templates.footerBlock; },
          modelEvents: {
              'change:styles.block.backgroundColor change:styles.text.fontColor change:styles.text.fontFamily change:styles.text.fontSize change:styles.text.textAlign change:styles.link.fontColor change:styles.link.textDecoration': 'render',
          },
          onDragSubstituteBy: function() { return Module.FooterWidgetView; },
          onRender: function() {
              this.toolsView = new Module.FooterBlockToolsView({ model: this.model });
              this.toolsRegion.show(this.toolsView);
          },
          onDomRefresh: function() {
              this.attachTextEditor();
          },
          attachTextEditor: function() {
              var that = this;
              //this.$('.mailpoet_content').tinymce({
                  //inline: true,

                  //menubar: false,
                  //toolbar: "bold italic link unlink forecolor mailpoet_custom_fields",

                  //valid_elements: "p[class|style],span[class|style],a[href|class|title|target|style],strong[class|style],em[class|style],strike,br",
                  //invalid_elements: "script",
                  //style_formats: [
                      //{title: 'Paragraph', block: 'p'},
                  //],

                  //plugins: "wplink textcolor mailpoet_custom_fields",

                  //setup: function(editor) {
                      //editor.on('change', function(e) {
                          //that.model.set('text', editor.getContent());
                      //});

                      //editor.on('focus', function(e) {
                          //that.disableShowingTools();
                      //});

                      //editor.on('blur', function(e) {
                          //that.enableShowingTools();
                      //});
                  //},

                  //mailpoet_custom_fields: App.getConfig().get('customFields').toJSON(),
                  //mailpoet_custom_fields_window_title: App.getConfig().get('translations.customFieldsWindowTitle'),
              //});
          },
      });

      Module.FooterBlockToolsView = base.BlockToolsView.extend({
          getSettingsView: function() { return Module.FooterBlockSettingsView; },
      });

      Module.FooterBlockSettingsView = base.BlockSettingsView.extend({
          getTemplate: function() { return templates.footerBlockSettings; },
          events: function() {
              return {
                  "change .mailpoet_field_footer_text_color": _.partial(this.changeColorField, "styles.text.fontColor"),
                  "change .mailpoet_field_footer_text_font_family": _.partial(this.changeField, "styles.text.fontFamily"),
                  "change .mailpoet_field_footer_text_size": _.partial(this.changeField, "styles.text.fontSize"),
                  "change #mailpoet_field_footer_link_color": _.partial(this.changeColorField, "styles.link.fontColor"),
                  "change #mailpoet_field_footer_link_underline": function(event) {
                  this.model.set('styles.link.textDecoration', (event.target.checked) ? event.target.value : 'none');
              },
                  "change .mailpoet_field_footer_background_color": _.partial(this.changeColorField, "styles.block.backgroundColor"),
                  "change .mailpoet_field_footer_alignment": _.partial(this.changeField, "styles.text.textAlign"),
                  "click .mailpoet_done_editing": "close",
              };
          },
          behaviors: {
              ColorPickerBehavior: {},
          },
          templateHelpers: function() {
              return {
                  model: this.model.toJSON(),
                  availableStyles: App.getAvailableStyles().toJSON(),
              };
          },
      });

      Module.FooterWidgetView = base.WidgetView.extend({
          getTemplate: function() { return templates.footerInsertion; },
          behaviors: {
              DraggableBehavior: {
                  cloneOriginal: true,
                  drop: function() {
                      return new Module.FooterBlockModel();
                  },
              }
          },
      });

      App.on('before:start', function() {
          App.registerBlockType('footer', {
              blockModel: Module.FooterBlockModel,
              blockView: Module.FooterBlockView,
          });

          App.registerWidget({
              name: 'footer',
              widgetView: Module.FooterWidgetView,
              priority: 99,
          });
      });
  });

});
