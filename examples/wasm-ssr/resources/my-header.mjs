function MyHeader({ html }) {
  return html`<style>
      h1 {
        color: red;
      }
    </style>
    <h1><slot></slot></h1>`;
}
