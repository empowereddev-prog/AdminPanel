(function () {
    'use strict';

    const source = document.getElementById('description');
    if (!source) return;

    const form = source.form;
    const buttons = Array.from(form.querySelectorAll('[type="submit"]'));
    const originalDisabled = buttons.map(button => button.disabled);
    buttons.forEach(button => { button.disabled = true; });
    const restoreButtons = () => buttons.forEach((button, i) => { button.disabled = originalDisabled[i]; });

    if (!window.CKEDITOR) {
        showError();
        return;
    }

    const {
        ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Underline,
        Strikethrough, Superscript, Subscript, RemoveFormat, FontFamily, FontSize,
        FontColor, FontBackgroundColor, Alignment, List, Indent, IndentBlock, Link,
        Table, TableToolbar, TableProperties, TableCellProperties, BlockQuote,
        HorizontalLine, GeneralHtmlSupport, Image, ImageCaption, ImageStyle, ImageResize, Plugin, Command, createDropdown,
        addListToDropdown, Collection, Model
    } = window.CKEDITOR;

    // Keep Summernote's line spacing control without a premium plugin.
    class LineHeightCommand extends Command {
        refresh() {
            const block = this.editor.model.document.selection.getSelectedBlocks().next().value;
            this.value = block && block.getAttribute('articleLineHeight');
            this.isEnabled = !!block;
        }
        execute({ value }) {
            const editor = this.editor;
            editor.model.change(writer => {
                for (const block of editor.model.document.selection.getSelectedBlocks()) {
                    if (!editor.model.schema.checkAttribute(block, 'articleLineHeight')) continue;
                    if (value) writer.setAttribute('articleLineHeight', value, block);
                    else writer.removeAttribute('articleLineHeight', block);
                }
            });
        }
    }

    class ArticleLineHeight extends Plugin {
        init() {
            const editor = this.editor;
            editor.model.schema.extend('$block', { allowAttributes: ['articleLineHeight'] });
            editor.model.schema.setAttributeProperties('articleLineHeight', { isFormatting: true });
            editor.conversion.for('upcast').attributeToAttribute({
                view: { key: 'style', value: { 'line-height': /[\s\S]+/ } },
                model: { key: 'articleLineHeight', value: element => element.getStyle('line-height') }
            });
            editor.conversion.for('downcast').attributeToAttribute({
                model: 'articleLineHeight',
                view: value => ({ key: 'style', value: { 'line-height': value } })
            });
            editor.commands.add('articleLineHeight', new LineHeightCommand(editor));
            editor.ui.componentFactory.add('articleLineHeight', locale => {
                const dropdown = createDropdown(locale);
                const command = editor.commands.get('articleLineHeight');
                dropdown.buttonView.set({ label: 'Line spacing', withText: true, tooltip: true });
                dropdown.bind('isEnabled').to(command, 'isEnabled');
                const items = new Collection();
                for (const value of ['', '1', '1.15', '1.5', '2', '2.5', '3']) {
                    const model = new Model({ label: value || 'Default', withText: true, value });
                    model.bind('isOn').to(command, 'value', current => (current || '') === value);
                    items.add({ type: 'button', model });
                }
                addListToDropdown(dropdown, items);
                this.listenTo(dropdown, 'execute', event => {
                    editor.execute('articleLineHeight', { value: event.source.value });
                    editor.editing.view.focus();
                });
                return dropdown;
            });
        }
    }

    ClassicEditor.create(source, {
        plugins: [
            Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough,
            Superscript, Subscript, RemoveFormat, FontFamily, FontSize, FontColor,
            FontBackgroundColor, Alignment, List, Indent, IndentBlock, Link, Table,
            TableToolbar, TableProperties, TableCellProperties, BlockQuote,
            HorizontalLine, GeneralHtmlSupport, Image, ImageCaption, ImageStyle, ImageResize, ArticleLineHeight
        ],
        toolbar: {
            items: [
                'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'underline',
                'strikethrough', 'superscript', 'subscript', 'removeFormat', '|',
                'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                'bulletedList', 'numberedList', 'alignment', 'outdent', 'indent',
                'articleLineHeight', '|', 'link', 'insertTable', 'horizontalLine', 'blockQuote'
            ],
            shouldNotGroupWhenFull: true
        },
        fontFamily: {
            options: ['default', 'Arial, Helvetica, sans-serif', 'Helvetica, Arial, sans-serif',
                'Comic Sans MS, cursive', 'Courier New, Courier, monospace',
                'Merriweather, serif', 'Times New Roman, Times, serif',
                'Verdana, Geneva, sans-serif', 'sans-serif'],
            supportAllValues: true
        },
        fontSize: { options: [10, 12, 14, 'default', 18, 20, 24, 28, 36], supportAllValues: true },
        table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'] },
        link: { addTargetToExternalLinks: true, defaultProtocol: 'https://' },
        // Preserve legacy article formatting while keeping scripts and event handlers out.
        htmlSupport: {
            allow: [{ name: /^(p|div|span|font|br|hr|h[1-6]|strong|b|em|i|u|s|strike|sup|sub|ul|ol|li|a|table|thead|tbody|tfoot|tr|td|th|blockquote)$/, styles: true,
                attributes: ['face', 'color', 'size', 'align', 'width', 'height', 'cellpadding', 'cellspacing', 'border'] }]
        }
    }).then(editor => {
        window.articleEditor = editor;
        editor.model.document.on('change:data', () => editor.updateSourceElement());
        form.addEventListener('submit', () => editor.updateSourceElement());
        restoreButtons();
    }).catch(error => {
        console.error('Article editor failed to initialize', error);
        showError();
    });

    function showError() {
        const message = document.createElement('div');
        message.className = 'alert alert-danger';
        message.setAttribute('role', 'alert');
        message.textContent = 'The article editor could not load. Please refresh the page before saving.';
        source.insertAdjacentElement('beforebegin', message);
    }
})();
