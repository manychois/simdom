const { createApp } = Vue;

const AppData = [];

const app = createApp({
  data() {
    return {
      url: '',
      htmlSnippet: '',
      childNodes: [],
      jsonText: '',
    };
  },
  methods: {
    async parse() {
      this.childNodes = [];
      this.jsonText = '';
      const formData = new FormData();
      formData.append('url', this.url);
      formData.append('htmlSnippet', this.htmlSnippet);
      try {
        const response = await fetch('/parse.php', { method: 'POST', body: formData });
        if (response.ok) {
          const parseResult = await response.json();
          if (parseResult.errorMsg) {
            alert(parseResult.errorMsg);
            return;
          }
          this.childNodes = parseResult.result;
          this.jsonText = JSON.stringify(parseResult.result, null, 2);
        } else {
          alert(await response.text());
        }
      } catch (error) {
        alert(error);
      }
    },
  },
});
app.mixin({
  methods: {
    getNsLabel(ns) {
      if (ns === 'http://www.w3.org/1999/xhtml') return 'HTML';
      if (ns === 'http://www.w3.org/2000/svg') return 'SVG';
      if (ns === 'http://www.w3.org/1998/Math/MathML') return 'MathML';
      return 'Others';
    },
    formatText(text) {
      let s = text;
      if (s.length > 80) {
        s = s.substr(0, 30) + '...' + s.substr(s.length - 30);
      }
      if (/^\s+$/.test(s)) {
        s = text.replace(/\n/g, '⏎').replace(/\t/g, '⇥').replace(/ /g, '▯');
      }
      return s;
    }
  }
});
app.component('Node', {
  props: ['node'],
  data() {
    return {
      concreteType: this.node.type,
    };
  },
  template: `<component :is="concreteType" :node="node"></component>`,
});
app.component('Element', {
  props: ['node'],
  template: `
  <div class="element">
    <div class="element__header">
      <span class="node-type" title="Element">E</span>
      <span class="node-ns" :title="node.namespaceURI">{{ getNsLabel(node.namespaceURI) }}</span>
      <span class="element__tag-name">{{ node.tagName }}</span>
      <table v-if="node.attributes" class="element__attr-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Value</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="attr in node.attributes">
            <td><span v-if="attr.prefix">(prefix: {{attr.prefix}}) </span>{{ attr.localName }}</td>
            <td>{{ formatText(attr.value) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <ul v-if="node?.childNodes?.length">
      <li v-for="cn in node.childNodes"><Node :node="cn" /></li>
    </ul>
  </div>`,
});
app.component('Text', {
  props: ['node'],
  template: `
  <div class="text">
    <span class="node-type" title="Text">T</span>
    {{ formatText(node.data) }}
  </div>`,
});
app.component('Comment', {
  props: ['node'],
  template: `
  <div class="comment">
    <span class="node-type" title="Comment">C</span>
    <span class="comment__data">{{ formatText(node.data) }}</span>
  </div>`,
});
app.component('Doctype', {
  props: ['node'],
  template: `
  <div class="doctype">
    <span class="node-type" title="Doctype">DT</span>
    <table class="doctype__table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Public ID</th>
          <th>System ID</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{{ node.name }}</td>
          <td>{{ node.publicId }}</td>
          <td>{{ node.systemId }}</td>
        </tr>
      </tbody>
    </table>
  </div>`,
});
app.mount('#app');
