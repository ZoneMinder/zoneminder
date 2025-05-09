# Pro sidebar template

Responsive layout with advanced sidebar menu built with SCSS and vanilla Javascript

## Demo

[See it live](https://azouaoui-med.github.io/pro-sidebar-template)

## Screenshot

![Pro Sidebar](https://user-images.githubusercontent.com/25878302/215290325-e5c6043b-4411-404c-83b8-dcc227df70ad.jpg)

## Installation

```
# clone the repo
$ git clone https://github.com/azouaoui-med/pro-sidebar-template.git my-project

# go into app's directory
$ cd my-project

# install app's dependencies
$ yarn install

```

## Usage

```
# serve with hot reload at localhost:8080
$ yarn start

# build app for production
$ yarn build

```

## Documentation

### Layout

The layout for this template is based on [css pro layout](https://github.com/azouaoui-med/css-pro-layout) package, please refer to the [docs](https://azouaoui-med.github.io/css-pro-layout/) for more information

### Sidebar

Responsive navigation element for building vertical menu items

**Sidebar Image**

Adding background image requires adding `.has-bg-image` class to sidebar component, and the image needs to be inside `.image-wrapper` component

```html
<aside id="sidebar" class="sidebar break-point-lg has-bg-image">
  <div class="image-wrapper">
    <img src="assets/images/sidebar-bg.jpg" alt="sidebar background" />
  </div>
  <div class="sidebar-layout">
    <!-- Content here -->
  </div>
</aside>
```

### Sidebar Layout

Sidebar comes with layout support for better organization of the inner structure

```html
<aside id="sidebar" class="sidebar break-point-lg">
  <div class="sidebar-layout">
    <div class="sidebar-header">
      <!-- Header content here -->
    </div>
    <div class="sidebar-content">
      <!-- Content here -->
    </div>
    <div class="sidebar-footer">
      <!-- Footer content here -->
    </div>
  </div>
</aside>
```

More on the sidebar [here](https://azouaoui-med.github.io/css-pro-layout/docs/reference/sidebar)

### Menu

Wrapper component that groups all menu items

```html
<nav class="menu">
  <!-- Content here -->
</nav>
```

**Open current submenu**

Use `.open-current-submenu` to enable opening only one submenu component at a time

```html
<nav class="menu open-current-submenu">
  <!-- Content here -->
</nav>
```

**Icon shape**

A set of classes are provided to restyle menu icons

- `.icon-shape-square`
- `.icon-shape-rounded`
- `.icon-shape-circle`

```html
<nav class="menu icon-shape-circle">
  <!-- Content here -->
</nav>
```

### Menu Item

Building menu item requires having `.menu-item` class in the wrapper and `.menu-title` for the text

```html
<nav class="menu">
  <ul>
    <li class="menu-item">
      <a href="#">
        <span class="menu-title">menu text</span>
      </a>
    </li>
    <!-- More menu items -->
  </ul>
</nav>
```

**Menu Icon**

Use `.menu-icon` to add an icon to menu items

```html
<nav class="menu">
  <ul>
    <li class="menu-item">
      <a href="#">
        <span class="menu-icon">
          <i class="ri-service-fill"></i>
        </span>
        <span class="menu-title">menu text</span>
      </a>
    </li>
    <!-- More menu items -->
  </ul>
</nav>
```

**Prefix & Suffix**

Menu item also supports having prefix and suffix components

```html
<nav class="menu">
  <ul>
    <li class="menu-item">
      <a href="#">
        <span class="menu-icon">
          <i class="ri-service-fill"></i>
        </span>
        <span class="menu-prefix">prefix</span>
        <span class="menu-title">menu text</span>
        <span class="menu-suffix">suffix</span>
      </a>
    </li>
    <!-- More menu items -->
  </ul>
</nav>
```

### Sub Menu

Add `.sub-menu` class to menu item and create a wrapper component with `sub-menu-list` class to group sub menu items

> Its possible to have unlimited nesting menu items

```html
<nav class="menu">
  <ul>
    <li class="menu-item sub-menu">
      <a href="#">
        <span class="menu-title">menu text</span>
      </a>
      <div class="sub-menu-list">
        <li class="menu-item">
          <a href="#">
            <span class="menu-title">sub menu text</span>
          </a>
        </li>
        <!-- More sub menu items -->
      </div>
    </li>
    <!-- More menu items -->
  </ul>
</nav>
```

**Open default**

Use `.open` class to have sub menu expanded by default

```html
<nav class="menu">
  <ul>
    <li class="menu-item sub-menu open">
      <a href="#">
        <span class="menu-title">menu text</span>
      </a>
      <div class="sub-menu-list">
        <li class="menu-item">
          <a href="#">
            <span class="menu-title">sub menu text</span>
          </a>
        </li>
        <!-- More sub menu items -->
      </div>
    </li>
    <!-- More menu items -->
  </ul>
</nav>
```

### Customization

Update SCSS variables in `src/styles/_variables.scss` to customize the template

```scss
$text-color: #b3b8d4;
$secondary-text-color: #dee2ec;
$bg-color: #0c1e35;
$secondary-bg-color: #0b1a2c;
$border-color: rgba(#535d7d, 0.3);
$sidebar-header-height: 64px;
$sidebar-footer-height: 64px;
```

## License

This code is released under the [MIT](https://github.com/azouaoui-med/pro-sidebar-template/blob/gh-pages/LICENSE) license.
