(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('jquery')) :
  typeof define === 'function' && define.amd ? define(['exports', 'jquery'], factory) :
  (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global.BootstrapTable = {}, global.jQuery));
})(this, (function (exports, $$a) { 'use strict';

  function _interopDefaultLegacy (e) { return e && typeof e === 'object' && 'default' in e ? e : { 'default': e }; }

  var $__default = /*#__PURE__*/_interopDefaultLegacy($$a);

  function _typeof(obj) {
    "@babel/helpers - typeof";

    return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) {
      return typeof obj;
    } : function (obj) {
      return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    }, _typeof(obj);
  }

  var commonjsGlobal = typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : typeof global !== 'undefined' ? global : typeof self !== 'undefined' ? self : {};

  var check = function (it) {
    return it && it.Math == Math && it;
  };

  // https://github.com/zloirock/core-js/issues/86#issuecomment-115759028
  var global$e =
    // eslint-disable-next-line es-x/no-global-this -- safe
    check(typeof globalThis == 'object' && globalThis) ||
    check(typeof window == 'object' && window) ||
    // eslint-disable-next-line no-restricted-globals -- safe
    check(typeof self == 'object' && self) ||
    check(typeof commonjsGlobal == 'object' && commonjsGlobal) ||
    // eslint-disable-next-line no-new-func -- fallback
    (function () { return this; })() || Function('return this')();

  var objectGetOwnPropertyDescriptor = {};

  var fails$n = function (exec) {
    try {
      return !!exec();
    } catch (error) {
      return true;
    }
  };

  var fails$m = fails$n;

  // Detect IE8's incomplete defineProperty implementation
  var descriptors = !fails$m(function () {
    // eslint-disable-next-line es-x/no-object-defineproperty -- required for testing
    return Object.defineProperty({}, 1, { get: function () { return 7; } })[1] != 7;
  });

  var fails$l = fails$n;

  var functionBindNative = !fails$l(function () {
    // eslint-disable-next-line es-x/no-function-prototype-bind -- safe
    var test = (function () { /* empty */ }).bind();
    // eslint-disable-next-line no-prototype-builtins -- safe
    return typeof test != 'function' || test.hasOwnProperty('prototype');
  });

  var NATIVE_BIND$3 = functionBindNative;

  var call$b = Function.prototype.call;

  var functionCall = NATIVE_BIND$3 ? call$b.bind(call$b) : function () {
    return call$b.apply(call$b, arguments);
  };

  var objectPropertyIsEnumerable = {};

  var $propertyIsEnumerable = {}.propertyIsEnumerable;
  // eslint-disable-next-line es-x/no-object-getownpropertydescriptor -- safe
  var getOwnPropertyDescriptor$1 = Object.getOwnPropertyDescriptor;

  // Nashorn ~ JDK8 bug
  var NASHORN_BUG = getOwnPropertyDescriptor$1 && !$propertyIsEnumerable.call({ 1: 2 }, 1);

  // `Object.prototype.propertyIsEnumerable` method implementation
  // https://tc39.es/ecma262/#sec-object.prototype.propertyisenumerable
  objectPropertyIsEnumerable.f = NASHORN_BUG ? function propertyIsEnumerable(V) {
    var descriptor = getOwnPropertyDescriptor$1(this, V);
    return !!descriptor && descriptor.enumerable;
  } : $propertyIsEnumerable;

  var createPropertyDescriptor$3 = function (bitmap, value) {
    return {
      enumerable: !(bitmap & 1),
      configurable: !(bitmap & 2),
      writable: !(bitmap & 4),
      value: value
    };
  };

  var NATIVE_BIND$2 = functionBindNative;

  var FunctionPrototype$2 = Function.prototype;
  var bind$2 = FunctionPrototype$2.bind;
  var call$a = FunctionPrototype$2.call;
  var uncurryThis$m = NATIVE_BIND$2 && bind$2.bind(call$a, call$a);

  var functionUncurryThis = NATIVE_BIND$2 ? function (fn) {
    return fn && uncurryThis$m(fn);
  } : function (fn) {
    return fn && function () {
      return call$a.apply(fn, arguments);
    };
  };

  var uncurryThis$l = functionUncurryThis;

  var toString$a = uncurryThis$l({}.toString);
  var stringSlice$5 = uncurryThis$l(''.slice);

  var classofRaw$1 = function (it) {
    return stringSlice$5(toString$a(it), 8, -1);
  };

  var uncurryThis$k = functionUncurryThis;
  var fails$k = fails$n;
  var classof$7 = classofRaw$1;

  var $Object$3 = Object;
  var split = uncurryThis$k(''.split);

  // fallback for non-array-like ES3 and non-enumerable old V8 strings
  var indexedObject = fails$k(function () {
    // throws an error in rhino, see https://github.com/mozilla/rhino/issues/346
    // eslint-disable-next-line no-prototype-builtins -- safe
    return !$Object$3('z').propertyIsEnumerable(0);
  }) ? function (it) {
    return classof$7(it) == 'String' ? split(it, '') : $Object$3(it);
  } : $Object$3;

  var $TypeError$9 = TypeError;

  // `RequireObjectCoercible` abstract operation
  // https://tc39.es/ecma262/#sec-requireobjectcoercible
  var requireObjectCoercible$7 = function (it) {
    if (it == undefined) throw $TypeError$9("Can't call method on " + it);
    return it;
  };

  // toObject with fallback for non-array-like ES3 strings
  var IndexedObject$2 = indexedObject;
  var requireObjectCoercible$6 = requireObjectCoercible$7;

  var toIndexedObject$5 = function (it) {
    return IndexedObject$2(requireObjectCoercible$6(it));
  };

  // `IsCallable` abstract operation
  // https://tc39.es/ecma262/#sec-iscallable
  var isCallable$e = function (argument) {
    return typeof argument == 'function';
  };

  var isCallable$d = isCallable$e;

  var isObject$8 = function (it) {
    return typeof it == 'object' ? it !== null : isCallable$d(it);
  };

  var global$d = global$e;
  var isCallable$c = isCallable$e;

  var aFunction = function (argument) {
    return isCallable$c(argument) ? argument : undefined;
  };

  var getBuiltIn$5 = function (namespace, method) {
    return arguments.length < 2 ? aFunction(global$d[namespace]) : global$d[namespace] && global$d[namespace][method];
  };

  var uncurryThis$j = functionUncurryThis;

  var objectIsPrototypeOf = uncurryThis$j({}.isPrototypeOf);

  var getBuiltIn$4 = getBuiltIn$5;

  var engineUserAgent = getBuiltIn$4('navigator', 'userAgent') || '';

  var global$c = global$e;
  var userAgent$2 = engineUserAgent;

  var process = global$c.process;
  var Deno = global$c.Deno;
  var versions = process && process.versions || Deno && Deno.version;
  var v8 = versions && versions.v8;
  var match, version;

  if (v8) {
    match = v8.split('.');
    // in old Chrome, versions of V8 isn't V8 = Chrome / 10
    // but their correct versions are not interesting for us
    version = match[0] > 0 && match[0] < 4 ? 1 : +(match[0] + match[1]);
  }

  // BrowserFS NodeJS `process` polyfill incorrectly set `.v8` to `0.0`
  // so check `userAgent` even if `.v8` exists, but 0
  if (!version && userAgent$2) {
    match = userAgent$2.match(/Edge\/(\d+)/);
    if (!match || match[1] >= 74) {
      match = userAgent$2.match(/Chrome\/(\d+)/);
      if (match) version = +match[1];
    }
  }

  var engineV8Version = version;

  /* eslint-disable es-x/no-symbol -- required for testing */

  var V8_VERSION$2 = engineV8Version;
  var fails$j = fails$n;

  // eslint-disable-next-line es-x/no-object-getownpropertysymbols -- required for testing
  var nativeSymbol = !!Object.getOwnPropertySymbols && !fails$j(function () {
    var symbol = Symbol();
    // Chrome 38 Symbol has incorrect toString conversion
    // `get-own-property-symbols` polyfill symbols converted to object are not Symbol instances
    return !String(symbol) || !(Object(symbol) instanceof Symbol) ||
      // Chrome 38-40 symbols are not inherited from DOM collections prototypes to instances
      !Symbol.sham && V8_VERSION$2 && V8_VERSION$2 < 41;
  });

  /* eslint-disable es-x/no-symbol -- required for testing */

  var NATIVE_SYMBOL$1 = nativeSymbol;

  var useSymbolAsUid = NATIVE_SYMBOL$1
    && !Symbol.sham
    && typeof Symbol.iterator == 'symbol';

  var getBuiltIn$3 = getBuiltIn$5;
  var isCallable$b = isCallable$e;
  var isPrototypeOf$1 = objectIsPrototypeOf;
  var USE_SYMBOL_AS_UID$1 = useSymbolAsUid;

  var $Object$2 = Object;

  var isSymbol$2 = USE_SYMBOL_AS_UID$1 ? function (it) {
    return typeof it == 'symbol';
  } : function (it) {
    var $Symbol = getBuiltIn$3('Symbol');
    return isCallable$b($Symbol) && isPrototypeOf$1($Symbol.prototype, $Object$2(it));
  };

  var $String$2 = String;

  var tryToString$3 = function (argument) {
    try {
      return $String$2(argument);
    } catch (error) {
      return 'Object';
    }
  };

  var isCallable$a = isCallable$e;
  var tryToString$2 = tryToString$3;

  var $TypeError$8 = TypeError;

  // `Assert: IsCallable(argument) is true`
  var aCallable$3 = function (argument) {
    if (isCallable$a(argument)) return argument;
    throw $TypeError$8(tryToString$2(argument) + ' is not a function');
  };

  var aCallable$2 = aCallable$3;

  // `GetMethod` abstract operation
  // https://tc39.es/ecma262/#sec-getmethod
  var getMethod$4 = function (V, P) {
    var func = V[P];
    return func == null ? undefined : aCallable$2(func);
  };

  var call$9 = functionCall;
  var isCallable$9 = isCallable$e;
  var isObject$7 = isObject$8;

  var $TypeError$7 = TypeError;

  // `OrdinaryToPrimitive` abstract operation
  // https://tc39.es/ecma262/#sec-ordinarytoprimitive
  var ordinaryToPrimitive$1 = function (input, pref) {
    var fn, val;
    if (pref === 'string' && isCallable$9(fn = input.toString) && !isObject$7(val = call$9(fn, input))) return val;
    if (isCallable$9(fn = input.valueOf) && !isObject$7(val = call$9(fn, input))) return val;
    if (pref !== 'string' && isCallable$9(fn = input.toString) && !isObject$7(val = call$9(fn, input))) return val;
    throw $TypeError$7("Can't convert object to primitive value");
  };

  var shared$4 = {exports: {}};

  var global$b = global$e;

  // eslint-disable-next-line es-x/no-object-defineproperty -- safe
  var defineProperty$2 = Object.defineProperty;

  var defineGlobalProperty$3 = function (key, value) {
    try {
      defineProperty$2(global$b, key, { value: value, configurable: true, writable: true });
    } catch (error) {
      global$b[key] = value;
    } return value;
  };

  var global$a = global$e;
  var defineGlobalProperty$2 = defineGlobalProperty$3;

  var SHARED = '__core-js_shared__';
  var store$3 = global$a[SHARED] || defineGlobalProperty$2(SHARED, {});

  var sharedStore = store$3;

  var store$2 = sharedStore;

  (shared$4.exports = function (key, value) {
    return store$2[key] || (store$2[key] = value !== undefined ? value : {});
  })('versions', []).push({
    version: '3.22.8',
    mode: 'global',
    copyright: '© 2014-2022 Denis Pushkarev (zloirock.ru)',
    license: 'https://github.com/zloirock/core-js/blob/v3.22.8/LICENSE',
    source: 'https://github.com/zloirock/core-js'
  });

  var requireObjectCoercible$5 = requireObjectCoercible$7;

  var $Object$1 = Object;

  // `ToObject` abstract operation
  // https://tc39.es/ecma262/#sec-toobject
  var toObject$6 = function (argument) {
    return $Object$1(requireObjectCoercible$5(argument));
  };

  var uncurryThis$i = functionUncurryThis;
  var toObject$5 = toObject$6;

  var hasOwnProperty = uncurryThis$i({}.hasOwnProperty);

  // `HasOwnProperty` abstract operation
  // https://tc39.es/ecma262/#sec-hasownproperty
  // eslint-disable-next-line es-x/no-object-hasown -- safe
  var hasOwnProperty_1 = Object.hasOwn || function hasOwn(it, key) {
    return hasOwnProperty(toObject$5(it), key);
  };

  var uncurryThis$h = functionUncurryThis;

  var id = 0;
  var postfix = Math.random();
  var toString$9 = uncurryThis$h(1.0.toString);

  var uid$2 = function (key) {
    return 'Symbol(' + (key === undefined ? '' : key) + ')_' + toString$9(++id + postfix, 36);
  };

  var global$9 = global$e;
  var shared$3 = shared$4.exports;
  var hasOwn$7 = hasOwnProperty_1;
  var uid$1 = uid$2;
  var NATIVE_SYMBOL = nativeSymbol;
  var USE_SYMBOL_AS_UID = useSymbolAsUid;

  var WellKnownSymbolsStore = shared$3('wks');
  var Symbol$1 = global$9.Symbol;
  var symbolFor = Symbol$1 && Symbol$1['for'];
  var createWellKnownSymbol = USE_SYMBOL_AS_UID ? Symbol$1 : Symbol$1 && Symbol$1.withoutSetter || uid$1;

  var wellKnownSymbol$b = function (name) {
    if (!hasOwn$7(WellKnownSymbolsStore, name) || !(NATIVE_SYMBOL || typeof WellKnownSymbolsStore[name] == 'string')) {
      var description = 'Symbol.' + name;
      if (NATIVE_SYMBOL && hasOwn$7(Symbol$1, name)) {
        WellKnownSymbolsStore[name] = Symbol$1[name];
      } else if (USE_SYMBOL_AS_UID && symbolFor) {
        WellKnownSymbolsStore[name] = symbolFor(description);
      } else {
        WellKnownSymbolsStore[name] = createWellKnownSymbol(description);
      }
    } return WellKnownSymbolsStore[name];
  };

  var call$8 = functionCall;
  var isObject$6 = isObject$8;
  var isSymbol$1 = isSymbol$2;
  var getMethod$3 = getMethod$4;
  var ordinaryToPrimitive = ordinaryToPrimitive$1;
  var wellKnownSymbol$a = wellKnownSymbol$b;

  var $TypeError$6 = TypeError;
  var TO_PRIMITIVE = wellKnownSymbol$a('toPrimitive');

  // `ToPrimitive` abstract operation
  // https://tc39.es/ecma262/#sec-toprimitive
  var toPrimitive$1 = function (input, pref) {
    if (!isObject$6(input) || isSymbol$1(input)) return input;
    var exoticToPrim = getMethod$3(input, TO_PRIMITIVE);
    var result;
    if (exoticToPrim) {
      if (pref === undefined) pref = 'default';
      result = call$8(exoticToPrim, input, pref);
      if (!isObject$6(result) || isSymbol$1(result)) return result;
      throw $TypeError$6("Can't convert object to primitive value");
    }
    if (pref === undefined) pref = 'number';
    return ordinaryToPrimitive(input, pref);
  };

  var toPrimitive = toPrimitive$1;
  var isSymbol = isSymbol$2;

  // `ToPropertyKey` abstract operation
  // https://tc39.es/ecma262/#sec-topropertykey
  var toPropertyKey$3 = function (argument) {
    var key = toPrimitive(argument, 'string');
    return isSymbol(key) ? key : key + '';
  };

  var global$8 = global$e;
  var isObject$5 = isObject$8;

  var document$1 = global$8.document;
  // typeof document.createElement is 'object' in old IE
  var EXISTS$1 = isObject$5(document$1) && isObject$5(document$1.createElement);

  var documentCreateElement$2 = function (it) {
    return EXISTS$1 ? document$1.createElement(it) : {};
  };

  var DESCRIPTORS$7 = descriptors;
  var fails$i = fails$n;
  var createElement = documentCreateElement$2;

  // Thanks to IE8 for its funny defineProperty
  var ie8DomDefine = !DESCRIPTORS$7 && !fails$i(function () {
    // eslint-disable-next-line es-x/no-object-defineproperty -- required for testing
    return Object.defineProperty(createElement('div'), 'a', {
      get: function () { return 7; }
    }).a != 7;
  });

  var DESCRIPTORS$6 = descriptors;
  var call$7 = functionCall;
  var propertyIsEnumerableModule = objectPropertyIsEnumerable;
  var createPropertyDescriptor$2 = createPropertyDescriptor$3;
  var toIndexedObject$4 = toIndexedObject$5;
  var toPropertyKey$2 = toPropertyKey$3;
  var hasOwn$6 = hasOwnProperty_1;
  var IE8_DOM_DEFINE$1 = ie8DomDefine;

  // eslint-disable-next-line es-x/no-object-getownpropertydescriptor -- safe
  var $getOwnPropertyDescriptor$1 = Object.getOwnPropertyDescriptor;

  // `Object.getOwnPropertyDescriptor` method
  // https://tc39.es/ecma262/#sec-object.getownpropertydescriptor
  objectGetOwnPropertyDescriptor.f = DESCRIPTORS$6 ? $getOwnPropertyDescriptor$1 : function getOwnPropertyDescriptor(O, P) {
    O = toIndexedObject$4(O);
    P = toPropertyKey$2(P);
    if (IE8_DOM_DEFINE$1) try {
      return $getOwnPropertyDescriptor$1(O, P);
    } catch (error) { /* empty */ }
    if (hasOwn$6(O, P)) return createPropertyDescriptor$2(!call$7(propertyIsEnumerableModule.f, O, P), O[P]);
  };

  var objectDefineProperty = {};

  var DESCRIPTORS$5 = descriptors;
  var fails$h = fails$n;

  // V8 ~ Chrome 36-
  // https://bugs.chromium.org/p/v8/issues/detail?id=3334
  var v8PrototypeDefineBug = DESCRIPTORS$5 && fails$h(function () {
    // eslint-disable-next-line es-x/no-object-defineproperty -- required for testing
    return Object.defineProperty(function () { /* empty */ }, 'prototype', {
      value: 42,
      writable: false
    }).prototype != 42;
  });

  var isObject$4 = isObject$8;

  var $String$1 = String;
  var $TypeError$5 = TypeError;

  // `Assert: Type(argument) is Object`
  var anObject$b = function (argument) {
    if (isObject$4(argument)) return argument;
    throw $TypeError$5($String$1(argument) + ' is not an object');
  };

  var DESCRIPTORS$4 = descriptors;
  var IE8_DOM_DEFINE = ie8DomDefine;
  var V8_PROTOTYPE_DEFINE_BUG$1 = v8PrototypeDefineBug;
  var anObject$a = anObject$b;
  var toPropertyKey$1 = toPropertyKey$3;

  var $TypeError$4 = TypeError;
  // eslint-disable-next-line es-x/no-object-defineproperty -- safe
  var $defineProperty = Object.defineProperty;
  // eslint-disable-next-line es-x/no-object-getownpropertydescriptor -- safe
  var $getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;
  var ENUMERABLE = 'enumerable';
  var CONFIGURABLE$1 = 'configurable';
  var WRITABLE = 'writable';

  // `Object.defineProperty` method
  // https://tc39.es/ecma262/#sec-object.defineproperty
  objectDefineProperty.f = DESCRIPTORS$4 ? V8_PROTOTYPE_DEFINE_BUG$1 ? function defineProperty(O, P, Attributes) {
    anObject$a(O);
    P = toPropertyKey$1(P);
    anObject$a(Attributes);
    if (typeof O === 'function' && P === 'prototype' && 'value' in Attributes && WRITABLE in Attributes && !Attributes[WRITABLE]) {
      var current = $getOwnPropertyDescriptor(O, P);
      if (current && current[WRITABLE]) {
        O[P] = Attributes.value;
        Attributes = {
          configurable: CONFIGURABLE$1 in Attributes ? Attributes[CONFIGURABLE$1] : current[CONFIGURABLE$1],
          enumerable: ENUMERABLE in Attributes ? Attributes[ENUMERABLE] : current[ENUMERABLE],
          writable: false
        };
      }
    } return $defineProperty(O, P, Attributes);
  } : $defineProperty : function defineProperty(O, P, Attributes) {
    anObject$a(O);
    P = toPropertyKey$1(P);
    anObject$a(Attributes);
    if (IE8_DOM_DEFINE) try {
      return $defineProperty(O, P, Attributes);
    } catch (error) { /* empty */ }
    if ('get' in Attributes || 'set' in Attributes) throw $TypeError$4('Accessors not supported');
    if ('value' in Attributes) O[P] = Attributes.value;
    return O;
  };

  var DESCRIPTORS$3 = descriptors;
  var definePropertyModule$3 = objectDefineProperty;
  var createPropertyDescriptor$1 = createPropertyDescriptor$3;

  var createNonEnumerableProperty$5 = DESCRIPTORS$3 ? function (object, key, value) {
    return definePropertyModule$3.f(object, key, createPropertyDescriptor$1(1, value));
  } : function (object, key, value) {
    object[key] = value;
    return object;
  };

  var makeBuiltIn$2 = {exports: {}};

  var DESCRIPTORS$2 = descriptors;
  var hasOwn$5 = hasOwnProperty_1;

  var FunctionPrototype$1 = Function.prototype;
  // eslint-disable-next-line es-x/no-object-getownpropertydescriptor -- safe
  var getDescriptor = DESCRIPTORS$2 && Object.getOwnPropertyDescriptor;

  var EXISTS = hasOwn$5(FunctionPrototype$1, 'name');
  // additional protection from minified / mangled / dropped function names
  var PROPER = EXISTS && (function something() { /* empty */ }).name === 'something';
  var CONFIGURABLE = EXISTS && (!DESCRIPTORS$2 || (DESCRIPTORS$2 && getDescriptor(FunctionPrototype$1, 'name').configurable));

  var functionName = {
    EXISTS: EXISTS,
    PROPER: PROPER,
    CONFIGURABLE: CONFIGURABLE
  };

  var uncurryThis$g = functionUncurryThis;
  var isCallable$8 = isCallable$e;
  var store$1 = sharedStore;

  var functionToString = uncurryThis$g(Function.toString);

  // this helper broken in `core-js@3.4.1-3.4.4`, so we can't use `shared` helper
  if (!isCallable$8(store$1.inspectSource)) {
    store$1.inspectSource = function (it) {
      return functionToString(it);
    };
  }

  var inspectSource$3 = store$1.inspectSource;

  var global$7 = global$e;
  var isCallable$7 = isCallable$e;
  var inspectSource$2 = inspectSource$3;

  var WeakMap$1 = global$7.WeakMap;

  var nativeWeakMap = isCallable$7(WeakMap$1) && /native code/.test(inspectSource$2(WeakMap$1));

  var shared$2 = shared$4.exports;
  var uid = uid$2;

  var keys = shared$2('keys');

  var sharedKey$2 = function (key) {
    return keys[key] || (keys[key] = uid(key));
  };

  var hiddenKeys$4 = {};

  var NATIVE_WEAK_MAP = nativeWeakMap;
  var global$6 = global$e;
  var uncurryThis$f = functionUncurryThis;
  var isObject$3 = isObject$8;
  var createNonEnumerableProperty$4 = createNonEnumerableProperty$5;
  var hasOwn$4 = hasOwnProperty_1;
  var shared$1 = sharedStore;
  var sharedKey$1 = sharedKey$2;
  var hiddenKeys$3 = hiddenKeys$4;

  var OBJECT_ALREADY_INITIALIZED = 'Object already initialized';
  var TypeError$1 = global$6.TypeError;
  var WeakMap = global$6.WeakMap;
  var set, get, has;

  var enforce = function (it) {
    return has(it) ? get(it) : set(it, {});
  };

  var getterFor = function (TYPE) {
    return function (it) {
      var state;
      if (!isObject$3(it) || (state = get(it)).type !== TYPE) {
        throw TypeError$1('Incompatible receiver, ' + TYPE + ' required');
      } return state;
    };
  };

  if (NATIVE_WEAK_MAP || shared$1.state) {
    var store = shared$1.state || (shared$1.state = new WeakMap());
    var wmget = uncurryThis$f(store.get);
    var wmhas = uncurryThis$f(store.has);
    var wmset = uncurryThis$f(store.set);
    set = function (it, metadata) {
      if (wmhas(store, it)) throw new TypeError$1(OBJECT_ALREADY_INITIALIZED);
      metadata.facade = it;
      wmset(store, it, metadata);
      return metadata;
    };
    get = function (it) {
      return wmget(store, it) || {};
    };
    has = function (it) {
      return wmhas(store, it);
    };
  } else {
    var STATE = sharedKey$1('state');
    hiddenKeys$3[STATE] = true;
    set = function (it, metadata) {
      if (hasOwn$4(it, STATE)) throw new TypeError$1(OBJECT_ALREADY_INITIALIZED);
      metadata.facade = it;
      createNonEnumerableProperty$4(it, STATE, metadata);
      return metadata;
    };
    get = function (it) {
      return hasOwn$4(it, STATE) ? it[STATE] : {};
    };
    has = function (it) {
      return hasOwn$4(it, STATE);
    };
  }

  var internalState = {
    set: set,
    get: get,
    has: has,
    enforce: enforce,
    getterFor: getterFor
  };

  var fails$g = fails$n;
  var isCallable$6 = isCallable$e;
  var hasOwn$3 = hasOwnProperty_1;
  var DESCRIPTORS$1 = descriptors;
  var CONFIGURABLE_FUNCTION_NAME = functionName.CONFIGURABLE;
  var inspectSource$1 = inspectSource$3;
  var InternalStateModule = internalState;

  var enforceInternalState = InternalStateModule.enforce;
  var getInternalState$1 = InternalStateModule.get;
  // eslint-disable-next-line es-x/no-object-defineproperty -- safe
  var defineProperty$1 = Object.defineProperty;

  var CONFIGURABLE_LENGTH = DESCRIPTORS$1 && !fails$g(function () {
    return defineProperty$1(function () { /* empty */ }, 'length', { value: 8 }).length !== 8;
  });

  var TEMPLATE = String(String).split('String');

  var makeBuiltIn$1 = makeBuiltIn$2.exports = function (value, name, options) {
    if (String(name).slice(0, 7) === 'Symbol(') {
      name = '[' + String(name).replace(/^Symbol\(([^)]*)\)/, '$1') + ']';
    }
    if (options && options.getter) name = 'get ' + name;
    if (options && options.setter) name = 'set ' + name;
    if (!hasOwn$3(value, 'name') || (CONFIGURABLE_FUNCTION_NAME && value.name !== name)) {
      defineProperty$1(value, 'name', { value: name, configurable: true });
    }
    if (CONFIGURABLE_LENGTH && options && hasOwn$3(options, 'arity') && value.length !== options.arity) {
      defineProperty$1(value, 'length', { value: options.arity });
    }
    try {
      if (options && hasOwn$3(options, 'constructor') && options.constructor) {
        if (DESCRIPTORS$1) defineProperty$1(value, 'prototype', { writable: false });
      // in V8 ~ Chrome 53, prototypes of some methods, like `Array.prototype.values`, are non-writable
      } else if (value.prototype) value.prototype = undefined;
    } catch (error) { /* empty */ }
    var state = enforceInternalState(value);
    if (!hasOwn$3(state, 'source')) {
      state.source = TEMPLATE.join(typeof name == 'string' ? name : '');
    } return value;
  };

  // add fake Function#toString for correct work wrapped methods / constructors with methods like LoDash isNative
  // eslint-disable-next-line no-extend-native -- required
  Function.prototype.toString = makeBuiltIn$1(function toString() {
    return isCallable$6(this) && getInternalState$1(this).source || inspectSource$1(this);
  }, 'toString');

  var isCallable$5 = isCallable$e;
  var createNonEnumerableProperty$3 = createNonEnumerableProperty$5;
  var makeBuiltIn = makeBuiltIn$2.exports;
  var defineGlobalProperty$1 = defineGlobalProperty$3;

  var defineBuiltIn$4 = function (O, key, value, options) {
    if (!options) options = {};
    var simple = options.enumerable;
    var name = options.name !== undefined ? options.name : key;
    if (isCallable$5(value)) makeBuiltIn(value, name, options);
    if (options.global) {
      if (simple) O[key] = value;
      else defineGlobalProperty$1(key, value);
    } else {
      if (!options.unsafe) delete O[key];
      else if (O[key]) simple = true;
      if (simple) O[key] = value;
      else createNonEnumerableProperty$3(O, key, value);
    } return O;
  };

  var objectGetOwnPropertyNames = {};

  var ceil = Math.ceil;
  var floor$2 = Math.floor;

  // `Math.trunc` method
  // https://tc39.es/ecma262/#sec-math.trunc
  // eslint-disable-next-line es-x/no-math-trunc -- safe
  var mathTrunc = Math.trunc || function trunc(x) {
    var n = +x;
    return (n > 0 ? floor$2 : ceil)(n);
  };

  var trunc = mathTrunc;

  // `ToIntegerOrInfinity` abstract operation
  // https://tc39.es/ecma262/#sec-tointegerorinfinity
  var toIntegerOrInfinity$4 = function (argument) {
    var number = +argument;
    // eslint-disable-next-line no-self-compare -- NaN check
    return number !== number || number === 0 ? 0 : trunc(number);
  };

  var toIntegerOrInfinity$3 = toIntegerOrInfinity$4;

  var max$2 = Math.max;
  var min$3 = Math.min;

  // Helper for a popular repeating case of the spec:
  // Let integer be ? ToInteger(index).
  // If integer < 0, let result be max((length + integer), 0); else let result be min(integer, length).
  var toAbsoluteIndex$2 = function (index, length) {
    var integer = toIntegerOrInfinity$3(index);
    return integer < 0 ? max$2(integer + length, 0) : min$3(integer, length);
  };

  var toIntegerOrInfinity$2 = toIntegerOrInfinity$4;

  var min$2 = Math.min;

  // `ToLength` abstract operation
  // https://tc39.es/ecma262/#sec-tolength
  var toLength$4 = function (argument) {
    return argument > 0 ? min$2(toIntegerOrInfinity$2(argument), 0x1FFFFFFFFFFFFF) : 0; // 2 ** 53 - 1 == 9007199254740991
  };

  var toLength$3 = toLength$4;

  // `LengthOfArrayLike` abstract operation
  // https://tc39.es/ecma262/#sec-lengthofarraylike
  var lengthOfArrayLike$5 = function (obj) {
    return toLength$3(obj.length);
  };

  var toIndexedObject$3 = toIndexedObject$5;
  var toAbsoluteIndex$1 = toAbsoluteIndex$2;
  var lengthOfArrayLike$4 = lengthOfArrayLike$5;

  // `Array.prototype.{ indexOf, includes }` methods implementation
  var createMethod$3 = function (IS_INCLUDES) {
    return function ($this, el, fromIndex) {
      var O = toIndexedObject$3($this);
      var length = lengthOfArrayLike$4(O);
      var index = toAbsoluteIndex$1(fromIndex, length);
      var value;
      // Array#includes uses SameValueZero equality algorithm
      // eslint-disable-next-line no-self-compare -- NaN check
      if (IS_INCLUDES && el != el) while (length > index) {
        value = O[index++];
        // eslint-disable-next-line no-self-compare -- NaN check
        if (value != value) return true;
      // Array#indexOf ignores holes, Array#includes - not
      } else for (;length > index; index++) {
        if ((IS_INCLUDES || index in O) && O[index] === el) return IS_INCLUDES || index || 0;
      } return !IS_INCLUDES && -1;
    };
  };

  var arrayIncludes = {
    // `Array.prototype.includes` method
    // https://tc39.es/ecma262/#sec-array.prototype.includes
    includes: createMethod$3(true),
    // `Array.prototype.indexOf` method
    // https://tc39.es/ecma262/#sec-array.prototype.indexof
    indexOf: createMethod$3(false)
  };

  var uncurryThis$e = functionUncurryThis;
  var hasOwn$2 = hasOwnProperty_1;
  var toIndexedObject$2 = toIndexedObject$5;
  var indexOf$1 = arrayIncludes.indexOf;
  var hiddenKeys$2 = hiddenKeys$4;

  var push$4 = uncurryThis$e([].push);

  var objectKeysInternal = function (object, names) {
    var O = toIndexedObject$2(object);
    var i = 0;
    var result = [];
    var key;
    for (key in O) !hasOwn$2(hiddenKeys$2, key) && hasOwn$2(O, key) && push$4(result, key);
    // Don't enum bug & hidden keys
    while (names.length > i) if (hasOwn$2(O, key = names[i++])) {
      ~indexOf$1(result, key) || push$4(result, key);
    }
    return result;
  };

  // IE8- don't enum bug keys
  var enumBugKeys$3 = [
    'constructor',
    'hasOwnProperty',
    'isPrototypeOf',
    'propertyIsEnumerable',
    'toLocaleString',
    'toString',
    'valueOf'
  ];

  var internalObjectKeys$1 = objectKeysInternal;
  var enumBugKeys$2 = enumBugKeys$3;

  var hiddenKeys$1 = enumBugKeys$2.concat('length', 'prototype');

  // `Object.getOwnPropertyNames` method
  // https://tc39.es/ecma262/#sec-object.getownpropertynames
  // eslint-disable-next-line es-x/no-object-getownpropertynames -- safe
  objectGetOwnPropertyNames.f = Object.getOwnPropertyNames || function getOwnPropertyNames(O) {
    return internalObjectKeys$1(O, hiddenKeys$1);
  };

  var objectGetOwnPropertySymbols = {};

  // eslint-disable-next-line es-x/no-object-getownpropertysymbols -- safe
  objectGetOwnPropertySymbols.f = Object.getOwnPropertySymbols;

  var getBuiltIn$2 = getBuiltIn$5;
  var uncurryThis$d = functionUncurryThis;
  var getOwnPropertyNamesModule = objectGetOwnPropertyNames;
  var getOwnPropertySymbolsModule = objectGetOwnPropertySymbols;
  var anObject$9 = anObject$b;

  var concat$1 = uncurryThis$d([].concat);

  // all object keys, includes non-enumerable and symbols
  var ownKeys$1 = getBuiltIn$2('Reflect', 'ownKeys') || function ownKeys(it) {
    var keys = getOwnPropertyNamesModule.f(anObject$9(it));
    var getOwnPropertySymbols = getOwnPropertySymbolsModule.f;
    return getOwnPropertySymbols ? concat$1(keys, getOwnPropertySymbols(it)) : keys;
  };

  var hasOwn$1 = hasOwnProperty_1;
  var ownKeys = ownKeys$1;
  var getOwnPropertyDescriptorModule = objectGetOwnPropertyDescriptor;
  var definePropertyModule$2 = objectDefineProperty;

  var copyConstructorProperties$1 = function (target, source, exceptions) {
    var keys = ownKeys(source);
    var defineProperty = definePropertyModule$2.f;
    var getOwnPropertyDescriptor = getOwnPropertyDescriptorModule.f;
    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];
      if (!hasOwn$1(target, key) && !(exceptions && hasOwn$1(exceptions, key))) {
        defineProperty(target, key, getOwnPropertyDescriptor(source, key));
      }
    }
  };

  var fails$f = fails$n;
  var isCallable$4 = isCallable$e;

  var replacement = /#|\.prototype\./;

  var isForced$1 = function (feature, detection) {
    var value = data[normalize(feature)];
    return value == POLYFILL ? true
      : value == NATIVE ? false
      : isCallable$4(detection) ? fails$f(detection)
      : !!detection;
  };

  var normalize = isForced$1.normalize = function (string) {
    return String(string).replace(replacement, '.').toLowerCase();
  };

  var data = isForced$1.data = {};
  var NATIVE = isForced$1.NATIVE = 'N';
  var POLYFILL = isForced$1.POLYFILL = 'P';

  var isForced_1 = isForced$1;

  var global$5 = global$e;
  var getOwnPropertyDescriptor = objectGetOwnPropertyDescriptor.f;
  var createNonEnumerableProperty$2 = createNonEnumerableProperty$5;
  var defineBuiltIn$3 = defineBuiltIn$4;
  var defineGlobalProperty = defineGlobalProperty$3;
  var copyConstructorProperties = copyConstructorProperties$1;
  var isForced = isForced_1;

  /*
    options.target         - name of the target object
    options.global         - target is the global object
    options.stat           - export as static methods of target
    options.proto          - export as prototype methods of target
    options.real           - real prototype method for the `pure` version
    options.forced         - export even if the native feature is available
    options.bind           - bind methods to the target, required for the `pure` version
    options.wrap           - wrap constructors to preventing global pollution, required for the `pure` version
    options.unsafe         - use the simple assignment of property instead of delete + defineProperty
    options.sham           - add a flag to not completely full polyfills
    options.enumerable     - export as enumerable property
    options.dontCallGetSet - prevent calling a getter on target
    options.name           - the .name of the function if it does not match the key
  */
  var _export = function (options, source) {
    var TARGET = options.target;
    var GLOBAL = options.global;
    var STATIC = options.stat;
    var FORCED, target, key, targetProperty, sourceProperty, descriptor;
    if (GLOBAL) {
      target = global$5;
    } else if (STATIC) {
      target = global$5[TARGET] || defineGlobalProperty(TARGET, {});
    } else {
      target = (global$5[TARGET] || {}).prototype;
    }
    if (target) for (key in source) {
      sourceProperty = source[key];
      if (options.dontCallGetSet) {
        descriptor = getOwnPropertyDescriptor(target, key);
        targetProperty = descriptor && descriptor.value;
      } else targetProperty = target[key];
      FORCED = isForced(GLOBAL ? key : TARGET + (STATIC ? '.' : '#') + key, options.forced);
      // contained in target
      if (!FORCED && targetProperty !== undefined) {
        if (typeof sourceProperty == typeof targetProperty) continue;
        copyConstructorProperties(sourceProperty, targetProperty);
      }
      // add a flag to not completely full polyfills
      if (options.sham || (targetProperty && targetProperty.sham)) {
        createNonEnumerableProperty$2(sourceProperty, 'sham', true);
      }
      defineBuiltIn$3(target, key, sourceProperty, options);
    }
  };

  var uncurryThis$c = functionUncurryThis;
  var aCallable$1 = aCallable$3;
  var NATIVE_BIND$1 = functionBindNative;

  var bind$1 = uncurryThis$c(uncurryThis$c.bind);

  // optional / simple context binding
  var functionBindContext = function (fn, that) {
    aCallable$1(fn);
    return that === undefined ? fn : NATIVE_BIND$1 ? bind$1(fn, that) : function (/* ...args */) {
      return fn.apply(that, arguments);
    };
  };

  var classof$6 = classofRaw$1;

  // `IsArray` abstract operation
  // https://tc39.es/ecma262/#sec-isarray
  // eslint-disable-next-line es-x/no-array-isarray -- safe
  var isArray$2 = Array.isArray || function isArray(argument) {
    return classof$6(argument) == 'Array';
  };

  var wellKnownSymbol$9 = wellKnownSymbol$b;

  var TO_STRING_TAG$1 = wellKnownSymbol$9('toStringTag');
  var test$1 = {};

  test$1[TO_STRING_TAG$1] = 'z';

  var toStringTagSupport = String(test$1) === '[object z]';

  var TO_STRING_TAG_SUPPORT$2 = toStringTagSupport;
  var isCallable$3 = isCallable$e;
  var classofRaw = classofRaw$1;
  var wellKnownSymbol$8 = wellKnownSymbol$b;

  var TO_STRING_TAG = wellKnownSymbol$8('toStringTag');
  var $Object = Object;

  // ES3 wrong here
  var CORRECT_ARGUMENTS = classofRaw(function () { return arguments; }()) == 'Arguments';

  // fallback for IE11 Script Access Denied error
  var tryGet = function (it, key) {
    try {
      return it[key];
    } catch (error) { /* empty */ }
  };

  // getting tag from ES6+ `Object.prototype.toString`
  var classof$5 = TO_STRING_TAG_SUPPORT$2 ? classofRaw : function (it) {
    var O, tag, result;
    return it === undefined ? 'Undefined' : it === null ? 'Null'
      // @@toStringTag case
      : typeof (tag = tryGet(O = $Object(it), TO_STRING_TAG)) == 'string' ? tag
      // builtinTag case
      : CORRECT_ARGUMENTS ? classofRaw(O)
      // ES3 arguments fallback
      : (result = classofRaw(O)) == 'Object' && isCallable$3(O.callee) ? 'Arguments' : result;
  };

  var uncurryThis$b = functionUncurryThis;
  var fails$e = fails$n;
  var isCallable$2 = isCallable$e;
  var classof$4 = classof$5;
  var getBuiltIn$1 = getBuiltIn$5;
  var inspectSource = inspectSource$3;

  var noop = function () { /* empty */ };
  var empty = [];
  var construct = getBuiltIn$1('Reflect', 'construct');
  var constructorRegExp = /^\s*(?:class|function)\b/;
  var exec$2 = uncurryThis$b(constructorRegExp.exec);
  var INCORRECT_TO_STRING = !constructorRegExp.exec(noop);

  var isConstructorModern = function isConstructor(argument) {
    if (!isCallable$2(argument)) return false;
    try {
      construct(noop, empty, argument);
      return true;
    } catch (error) {
      return false;
    }
  };

  var isConstructorLegacy = function isConstructor(argument) {
    if (!isCallable$2(argument)) return false;
    switch (classof$4(argument)) {
      case 'AsyncFunction':
      case 'GeneratorFunction':
      case 'AsyncGeneratorFunction': return false;
    }
    try {
      // we can't check .prototype since constructors produced by .bind haven't it
      // `Function#toString` throws on some built-it function in some legacy engines
      // (for example, `DOMQuad` and similar in FF41-)
      return INCORRECT_TO_STRING || !!exec$2(constructorRegExp, inspectSource(argument));
    } catch (error) {
      return true;
    }
  };

  isConstructorLegacy.sham = true;

  // `IsConstructor` abstract operation
  // https://tc39.es/ecma262/#sec-isconstructor
  var isConstructor$2 = !construct || fails$e(function () {
    var called;
    return isConstructorModern(isConstructorModern.call)
      || !isConstructorModern(Object)
      || !isConstructorModern(function () { called = true; })
      || called;
  }) ? isConstructorLegacy : isConstructorModern;

  var isArray$1 = isArray$2;
  var isConstructor$1 = isConstructor$2;
  var isObject$2 = isObject$8;
  var wellKnownSymbol$7 = wellKnownSymbol$b;

  var SPECIES$3 = wellKnownSymbol$7('species');
  var $Array$1 = Array;

  // a part of `ArraySpeciesCreate` abstract operation
  // https://tc39.es/ecma262/#sec-arrayspeciescreate
  var arraySpeciesConstructor$1 = function (originalArray) {
    var C;
    if (isArray$1(originalArray)) {
      C = originalArray.constructor;
      // cross-realm fallback
      if (isConstructor$1(C) && (C === $Array$1 || isArray$1(C.prototype))) C = undefined;
      else if (isObject$2(C)) {
        C = C[SPECIES$3];
        if (C === null) C = undefined;
      }
    } return C === undefined ? $Array$1 : C;
  };

  var arraySpeciesConstructor = arraySpeciesConstructor$1;

  // `ArraySpeciesCreate` abstract operation
  // https://tc39.es/ecma262/#sec-arrayspeciescreate
  var arraySpeciesCreate$2 = function (originalArray, length) {
    return new (arraySpeciesConstructor(originalArray))(length === 0 ? 0 : length);
  };

  var bind = functionBindContext;
  var uncurryThis$a = functionUncurryThis;
  var IndexedObject$1 = indexedObject;
  var toObject$4 = toObject$6;
  var lengthOfArrayLike$3 = lengthOfArrayLike$5;
  var arraySpeciesCreate$1 = arraySpeciesCreate$2;

  var push$3 = uncurryThis$a([].push);

  // `Array.prototype.{ forEach, map, filter, some, every, find, findIndex, filterReject }` methods implementation
  var createMethod$2 = function (TYPE) {
    var IS_MAP = TYPE == 1;
    var IS_FILTER = TYPE == 2;
    var IS_SOME = TYPE == 3;
    var IS_EVERY = TYPE == 4;
    var IS_FIND_INDEX = TYPE == 6;
    var IS_FILTER_REJECT = TYPE == 7;
    var NO_HOLES = TYPE == 5 || IS_FIND_INDEX;
    return function ($this, callbackfn, that, specificCreate) {
      var O = toObject$4($this);
      var self = IndexedObject$1(O);
      var boundFunction = bind(callbackfn, that);
      var length = lengthOfArrayLike$3(self);
      var index = 0;
      var create = specificCreate || arraySpeciesCreate$1;
      var target = IS_MAP ? create($this, length) : IS_FILTER || IS_FILTER_REJECT ? create($this, 0) : undefined;
      var value, result;
      for (;length > index; index++) if (NO_HOLES || index in self) {
        value = self[index];
        result = boundFunction(value, index, O);
        if (TYPE) {
          if (IS_MAP) target[index] = result; // map
          else if (result) switch (TYPE) {
            case 3: return true;              // some
            case 5: return value;             // find
            case 6: return index;             // findIndex
            case 2: push$3(target, value);      // filter
          } else switch (TYPE) {
            case 4: return false;             // every
            case 7: push$3(target, value);      // filterReject
          }
        }
      }
      return IS_FIND_INDEX ? -1 : IS_SOME || IS_EVERY ? IS_EVERY : target;
    };
  };

  var arrayIteration = {
    // `Array.prototype.forEach` method
    // https://tc39.es/ecma262/#sec-array.prototype.foreach
    forEach: createMethod$2(0),
    // `Array.prototype.map` method
    // https://tc39.es/ecma262/#sec-array.prototype.map
    map: createMethod$2(1),
    // `Array.prototype.filter` method
    // https://tc39.es/ecma262/#sec-array.prototype.filter
    filter: createMethod$2(2),
    // `Array.prototype.some` method
    // https://tc39.es/ecma262/#sec-array.prototype.some
    some: createMethod$2(3),
    // `Array.prototype.every` method
    // https://tc39.es/ecma262/#sec-array.prototype.every
    every: createMethod$2(4),
    // `Array.prototype.find` method
    // https://tc39.es/ecma262/#sec-array.prototype.find
    find: createMethod$2(5),
    // `Array.prototype.findIndex` method
    // https://tc39.es/ecma262/#sec-array.prototype.findIndex
    findIndex: createMethod$2(6),
    // `Array.prototype.filterReject` method
    // https://github.com/tc39/proposal-array-filtering
    filterReject: createMethod$2(7)
  };

  var objectDefineProperties = {};

  var internalObjectKeys = objectKeysInternal;
  var enumBugKeys$1 = enumBugKeys$3;

  // `Object.keys` method
  // https://tc39.es/ecma262/#sec-object.keys
  // eslint-disable-next-line es-x/no-object-keys -- safe
  var objectKeys$1 = Object.keys || function keys(O) {
    return internalObjectKeys(O, enumBugKeys$1);
  };

  var DESCRIPTORS = descriptors;
  var V8_PROTOTYPE_DEFINE_BUG = v8PrototypeDefineBug;
  var definePropertyModule$1 = objectDefineProperty;
  var anObject$8 = anObject$b;
  var toIndexedObject$1 = toIndexedObject$5;
  var objectKeys = objectKeys$1;

  // `Object.defineProperties` method
  // https://tc39.es/ecma262/#sec-object.defineproperties
  // eslint-disable-next-line es-x/no-object-defineproperties -- safe
  objectDefineProperties.f = DESCRIPTORS && !V8_PROTOTYPE_DEFINE_BUG ? Object.defineProperties : function defineProperties(O, Properties) {
    anObject$8(O);
    var props = toIndexedObject$1(Properties);
    var keys = objectKeys(Properties);
    var length = keys.length;
    var index = 0;
    var key;
    while (length > index) definePropertyModule$1.f(O, key = keys[index++], props[key]);
    return O;
  };

  var getBuiltIn = getBuiltIn$5;

  var html$1 = getBuiltIn('document', 'documentElement');

  /* global ActiveXObject -- old IE, WSH */

  var anObject$7 = anObject$b;
  var definePropertiesModule = objectDefineProperties;
  var enumBugKeys = enumBugKeys$3;
  var hiddenKeys = hiddenKeys$4;
  var html = html$1;
  var documentCreateElement$1 = documentCreateElement$2;
  var sharedKey = sharedKey$2;

  var GT = '>';
  var LT = '<';
  var PROTOTYPE = 'prototype';
  var SCRIPT = 'script';
  var IE_PROTO = sharedKey('IE_PROTO');

  var EmptyConstructor = function () { /* empty */ };

  var scriptTag = function (content) {
    return LT + SCRIPT + GT + content + LT + '/' + SCRIPT + GT;
  };

  // Create object with fake `null` prototype: use ActiveX Object with cleared prototype
  var NullProtoObjectViaActiveX = function (activeXDocument) {
    activeXDocument.write(scriptTag(''));
    activeXDocument.close();
    var temp = activeXDocument.parentWindow.Object;
    activeXDocument = null; // avoid memory leak
    return temp;
  };

  // Create object with fake `null` prototype: use iframe Object with cleared prototype
  var NullProtoObjectViaIFrame = function () {
    // Thrash, waste and sodomy: IE GC bug
    var iframe = documentCreateElement$1('iframe');
    var JS = 'java' + SCRIPT + ':';
    var iframeDocument;
    iframe.style.display = 'none';
    html.appendChild(iframe);
    // https://github.com/zloirock/core-js/issues/475
    iframe.src = String(JS);
    iframeDocument = iframe.contentWindow.document;
    iframeDocument.open();
    iframeDocument.write(scriptTag('document.F=Object'));
    iframeDocument.close();
    return iframeDocument.F;
  };

  // Check for document.domain and active x support
  // No need to use active x approach when document.domain is not set
  // see https://github.com/es-shims/es5-shim/issues/150
  // variation of https://github.com/kitcambridge/es5-shim/commit/4f738ac066346
  // avoid IE GC bug
  var activeXDocument;
  var NullProtoObject = function () {
    try {
      activeXDocument = new ActiveXObject('htmlfile');
    } catch (error) { /* ignore */ }
    NullProtoObject = typeof document != 'undefined'
      ? document.domain && activeXDocument
        ? NullProtoObjectViaActiveX(activeXDocument) // old IE
        : NullProtoObjectViaIFrame()
      : NullProtoObjectViaActiveX(activeXDocument); // WSH
    var length = enumBugKeys.length;
    while (length--) delete NullProtoObject[PROTOTYPE][enumBugKeys[length]];
    return NullProtoObject();
  };

  hiddenKeys[IE_PROTO] = true;

  // `Object.create` method
  // https://tc39.es/ecma262/#sec-object.create
  // eslint-disable-next-line es-x/no-object-create -- safe
  var objectCreate = Object.create || function create(O, Properties) {
    var result;
    if (O !== null) {
      EmptyConstructor[PROTOTYPE] = anObject$7(O);
      result = new EmptyConstructor();
      EmptyConstructor[PROTOTYPE] = null;
      // add "__proto__" for Object.getPrototypeOf polyfill
      result[IE_PROTO] = O;
    } else result = NullProtoObject();
    return Properties === undefined ? result : definePropertiesModule.f(result, Properties);
  };

  var wellKnownSymbol$6 = wellKnownSymbol$b;
  var create$1 = objectCreate;
  var defineProperty = objectDefineProperty.f;

  var UNSCOPABLES = wellKnownSymbol$6('unscopables');
  var ArrayPrototype = Array.prototype;

  // Array.prototype[@@unscopables]
  // https://tc39.es/ecma262/#sec-array.prototype-@@unscopables
  if (ArrayPrototype[UNSCOPABLES] == undefined) {
    defineProperty(ArrayPrototype, UNSCOPABLES, {
      configurable: true,
      value: create$1(null)
    });
  }

  // add a key to Array.prototype[@@unscopables]
  var addToUnscopables$2 = function (key) {
    ArrayPrototype[UNSCOPABLES][key] = true;
  };

  var $$9 = _export;
  var $find = arrayIteration.find;
  var addToUnscopables$1 = addToUnscopables$2;

  var FIND = 'find';
  var SKIPS_HOLES = true;

  // Shouldn't skip holes
  if (FIND in []) Array(1)[FIND](function () { SKIPS_HOLES = false; });

  // `Array.prototype.find` method
  // https://tc39.es/ecma262/#sec-array.prototype.find
  $$9({ target: 'Array', proto: true, forced: SKIPS_HOLES }, {
    find: function find(callbackfn /* , that = undefined */) {
      return $find(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
    }
  });

  // https://tc39.es/ecma262/#sec-array.prototype-@@unscopables
  addToUnscopables$1(FIND);

  var TO_STRING_TAG_SUPPORT$1 = toStringTagSupport;
  var classof$3 = classof$5;

  // `Object.prototype.toString` method implementation
  // https://tc39.es/ecma262/#sec-object.prototype.tostring
  var objectToString = TO_STRING_TAG_SUPPORT$1 ? {}.toString : function toString() {
    return '[object ' + classof$3(this) + ']';
  };

  var TO_STRING_TAG_SUPPORT = toStringTagSupport;
  var defineBuiltIn$2 = defineBuiltIn$4;
  var toString$8 = objectToString;

  // `Object.prototype.toString` method
  // https://tc39.es/ecma262/#sec-object.prototype.tostring
  if (!TO_STRING_TAG_SUPPORT) {
    defineBuiltIn$2(Object.prototype, 'toString', toString$8, { unsafe: true });
  }

  var classof$2 = classof$5;

  var $String = String;

  var toString$7 = function (argument) {
    if (classof$2(argument) === 'Symbol') throw TypeError('Cannot convert a Symbol value to a string');
    return $String(argument);
  };

  // a string of all valid unicode whitespaces
  var whitespaces$2 = '\u0009\u000A\u000B\u000C\u000D\u0020\u00A0\u1680\u2000\u2001\u2002' +
    '\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200A\u202F\u205F\u3000\u2028\u2029\uFEFF';

  var uncurryThis$9 = functionUncurryThis;
  var requireObjectCoercible$4 = requireObjectCoercible$7;
  var toString$6 = toString$7;
  var whitespaces$1 = whitespaces$2;

  var replace$2 = uncurryThis$9(''.replace);
  var whitespace = '[' + whitespaces$1 + ']';
  var ltrim = RegExp('^' + whitespace + whitespace + '*');
  var rtrim = RegExp(whitespace + whitespace + '*$');

  // `String.prototype.{ trim, trimStart, trimEnd, trimLeft, trimRight }` methods implementation
  var createMethod$1 = function (TYPE) {
    return function ($this) {
      var string = toString$6(requireObjectCoercible$4($this));
      if (TYPE & 1) string = replace$2(string, ltrim, '');
      if (TYPE & 2) string = replace$2(string, rtrim, '');
      return string;
    };
  };

  var stringTrim = {
    // `String.prototype.{ trimLeft, trimStart }` methods
    // https://tc39.es/ecma262/#sec-string.prototype.trimstart
    start: createMethod$1(1),
    // `String.prototype.{ trimRight, trimEnd }` methods
    // https://tc39.es/ecma262/#sec-string.prototype.trimend
    end: createMethod$1(2),
    // `String.prototype.trim` method
    // https://tc39.es/ecma262/#sec-string.prototype.trim
    trim: createMethod$1(3)
  };

  var PROPER_FUNCTION_NAME$1 = functionName.PROPER;
  var fails$d = fails$n;
  var whitespaces = whitespaces$2;

  var non = '\u200B\u0085\u180E';

  // check that a method works with the correct list
  // of whitespaces and has a correct name
  var stringTrimForced = function (METHOD_NAME) {
    return fails$d(function () {
      return !!whitespaces[METHOD_NAME]()
        || non[METHOD_NAME]() !== non
        || (PROPER_FUNCTION_NAME$1 && whitespaces[METHOD_NAME].name !== METHOD_NAME);
    });
  };

  var $$8 = _export;
  var $trim = stringTrim.trim;
  var forcedStringTrimMethod = stringTrimForced;

  // `String.prototype.trim` method
  // https://tc39.es/ecma262/#sec-string.prototype.trim
  $$8({ target: 'String', proto: true, forced: forcedStringTrimMethod('trim') }, {
    trim: function trim() {
      return $trim(this);
    }
  });

  var anObject$6 = anObject$b;

  // `RegExp.prototype.flags` getter implementation
  // https://tc39.es/ecma262/#sec-get-regexp.prototype.flags
  var regexpFlags$1 = function () {
    var that = anObject$6(this);
    var result = '';
    if (that.hasIndices) result += 'd';
    if (that.global) result += 'g';
    if (that.ignoreCase) result += 'i';
    if (that.multiline) result += 'm';
    if (that.dotAll) result += 's';
    if (that.unicode) result += 'u';
    if (that.sticky) result += 'y';
    return result;
  };

  var call$6 = functionCall;
  var hasOwn = hasOwnProperty_1;
  var isPrototypeOf = objectIsPrototypeOf;
  var regExpFlags = regexpFlags$1;

  var RegExpPrototype$2 = RegExp.prototype;

  var regexpGetFlags = function (R) {
    var flags = R.flags;
    return flags === undefined && !('flags' in RegExpPrototype$2) && !hasOwn(R, 'flags') && isPrototypeOf(RegExpPrototype$2, R)
      ? call$6(regExpFlags, R) : flags;
  };

  var PROPER_FUNCTION_NAME = functionName.PROPER;
  var defineBuiltIn$1 = defineBuiltIn$4;
  var anObject$5 = anObject$b;
  var $toString = toString$7;
  var fails$c = fails$n;
  var getRegExpFlags = regexpGetFlags;

  var TO_STRING = 'toString';
  var RegExpPrototype$1 = RegExp.prototype;
  var n$ToString = RegExpPrototype$1[TO_STRING];

  var NOT_GENERIC = fails$c(function () { return n$ToString.call({ source: 'a', flags: 'b' }) != '/a/b'; });
  // FF44- RegExp#toString has a wrong name
  var INCORRECT_NAME = PROPER_FUNCTION_NAME && n$ToString.name != TO_STRING;

  // `RegExp.prototype.toString` method
  // https://tc39.es/ecma262/#sec-regexp.prototype.tostring
  if (NOT_GENERIC || INCORRECT_NAME) {
    defineBuiltIn$1(RegExp.prototype, TO_STRING, function toString() {
      var R = anObject$5(this);
      var pattern = $toString(R.source);
      var flags = $toString(getRegExpFlags(R));
      return '/' + pattern + '/' + flags;
    }, { unsafe: true });
  }

  var tryToString$1 = tryToString$3;

  var $TypeError$3 = TypeError;

  var deletePropertyOrThrow$1 = function (O, P) {
    if (!delete O[P]) throw $TypeError$3('Cannot delete property ' + tryToString$1(P) + ' of ' + tryToString$1(O));
  };

  var toPropertyKey = toPropertyKey$3;
  var definePropertyModule = objectDefineProperty;
  var createPropertyDescriptor = createPropertyDescriptor$3;

  var createProperty$2 = function (object, key, value) {
    var propertyKey = toPropertyKey(key);
    if (propertyKey in object) definePropertyModule.f(object, propertyKey, createPropertyDescriptor(0, value));
    else object[propertyKey] = value;
  };

  var toAbsoluteIndex = toAbsoluteIndex$2;
  var lengthOfArrayLike$2 = lengthOfArrayLike$5;
  var createProperty$1 = createProperty$2;

  var $Array = Array;
  var max$1 = Math.max;

  var arraySliceSimple = function (O, start, end) {
    var length = lengthOfArrayLike$2(O);
    var k = toAbsoluteIndex(start, length);
    var fin = toAbsoluteIndex(end === undefined ? length : end, length);
    var result = $Array(max$1(fin - k, 0));
    for (var n = 0; k < fin; k++, n++) createProperty$1(result, n, O[k]);
    result.length = n;
    return result;
  };

  var arraySlice$1 = arraySliceSimple;

  var floor$1 = Math.floor;

  var mergeSort = function (array, comparefn) {
    var length = array.length;
    var middle = floor$1(length / 2);
    return length < 8 ? insertionSort(array, comparefn) : merge(
      array,
      mergeSort(arraySlice$1(array, 0, middle), comparefn),
      mergeSort(arraySlice$1(array, middle), comparefn),
      comparefn
    );
  };

  var insertionSort = function (array, comparefn) {
    var length = array.length;
    var i = 1;
    var element, j;

    while (i < length) {
      j = i;
      element = array[i];
      while (j && comparefn(array[j - 1], element) > 0) {
        array[j] = array[--j];
      }
      if (j !== i++) array[j] = element;
    } return array;
  };

  var merge = function (array, left, right, comparefn) {
    var llength = left.length;
    var rlength = right.length;
    var lindex = 0;
    var rindex = 0;

    while (lindex < llength || rindex < rlength) {
      array[lindex + rindex] = (lindex < llength && rindex < rlength)
        ? comparefn(left[lindex], right[rindex]) <= 0 ? left[lindex++] : right[rindex++]
        : lindex < llength ? left[lindex++] : right[rindex++];
    } return array;
  };

  var arraySort = mergeSort;

  var fails$b = fails$n;

  var arrayMethodIsStrict$4 = function (METHOD_NAME, argument) {
    var method = [][METHOD_NAME];
    return !!method && fails$b(function () {
      // eslint-disable-next-line no-useless-call -- required for testing
      method.call(null, argument || function () { return 1; }, 1);
    });
  };

  var userAgent$1 = engineUserAgent;

  var firefox = userAgent$1.match(/firefox\/(\d+)/i);

  var engineFfVersion = !!firefox && +firefox[1];

  var UA = engineUserAgent;

  var engineIsIeOrEdge = /MSIE|Trident/.test(UA);

  var userAgent = engineUserAgent;

  var webkit = userAgent.match(/AppleWebKit\/(\d+)\./);

  var engineWebkitVersion = !!webkit && +webkit[1];

  var $$7 = _export;
  var uncurryThis$8 = functionUncurryThis;
  var aCallable = aCallable$3;
  var toObject$3 = toObject$6;
  var lengthOfArrayLike$1 = lengthOfArrayLike$5;
  var deletePropertyOrThrow = deletePropertyOrThrow$1;
  var toString$5 = toString$7;
  var fails$a = fails$n;
  var internalSort = arraySort;
  var arrayMethodIsStrict$3 = arrayMethodIsStrict$4;
  var FF = engineFfVersion;
  var IE_OR_EDGE = engineIsIeOrEdge;
  var V8 = engineV8Version;
  var WEBKIT = engineWebkitVersion;

  var test = [];
  var un$Sort = uncurryThis$8(test.sort);
  var push$2 = uncurryThis$8(test.push);

  // IE8-
  var FAILS_ON_UNDEFINED = fails$a(function () {
    test.sort(undefined);
  });
  // V8 bug
  var FAILS_ON_NULL = fails$a(function () {
    test.sort(null);
  });
  // Old WebKit
  var STRICT_METHOD$3 = arrayMethodIsStrict$3('sort');

  var STABLE_SORT = !fails$a(function () {
    // feature detection can be too slow, so check engines versions
    if (V8) return V8 < 70;
    if (FF && FF > 3) return;
    if (IE_OR_EDGE) return true;
    if (WEBKIT) return WEBKIT < 603;

    var result = '';
    var code, chr, value, index;

    // generate an array with more 512 elements (Chakra and old V8 fails only in this case)
    for (code = 65; code < 76; code++) {
      chr = String.fromCharCode(code);

      switch (code) {
        case 66: case 69: case 70: case 72: value = 3; break;
        case 68: case 71: value = 4; break;
        default: value = 2;
      }

      for (index = 0; index < 47; index++) {
        test.push({ k: chr + index, v: value });
      }
    }

    test.sort(function (a, b) { return b.v - a.v; });

    for (index = 0; index < test.length; index++) {
      chr = test[index].k.charAt(0);
      if (result.charAt(result.length - 1) !== chr) result += chr;
    }

    return result !== 'DGBEFHACIJK';
  });

  var FORCED$1 = FAILS_ON_UNDEFINED || !FAILS_ON_NULL || !STRICT_METHOD$3 || !STABLE_SORT;

  var getSortCompare = function (comparefn) {
    return function (x, y) {
      if (y === undefined) return -1;
      if (x === undefined) return 1;
      if (comparefn !== undefined) return +comparefn(x, y) || 0;
      return toString$5(x) > toString$5(y) ? 1 : -1;
    };
  };

  // `Array.prototype.sort` method
  // https://tc39.es/ecma262/#sec-array.prototype.sort
  $$7({ target: 'Array', proto: true, forced: FORCED$1 }, {
    sort: function sort(comparefn) {
      if (comparefn !== undefined) aCallable(comparefn);

      var array = toObject$3(this);

      if (STABLE_SORT) return comparefn === undefined ? un$Sort(array) : un$Sort(array, comparefn);

      var items = [];
      var arrayLength = lengthOfArrayLike$1(array);
      var itemsLength, index;

      for (index = 0; index < arrayLength; index++) {
        if (index in array) push$2(items, array[index]);
      }

      internalSort(items, getSortCompare(comparefn));

      itemsLength = items.length;
      index = 0;

      while (index < itemsLength) array[index] = items[index++];
      while (index < arrayLength) deletePropertyOrThrow(array, index++);

      return array;
    }
  });

  var fails$9 = fails$n;
  var global$4 = global$e;

  // babel-minify and Closure Compiler transpiles RegExp('a', 'y') -> /a/y and it causes SyntaxError
  var $RegExp$2 = global$4.RegExp;

  var UNSUPPORTED_Y$2 = fails$9(function () {
    var re = $RegExp$2('a', 'y');
    re.lastIndex = 2;
    return re.exec('abcd') != null;
  });

  // UC Browser bug
  // https://github.com/zloirock/core-js/issues/1008
  var MISSED_STICKY = UNSUPPORTED_Y$2 || fails$9(function () {
    return !$RegExp$2('a', 'y').sticky;
  });

  var BROKEN_CARET = UNSUPPORTED_Y$2 || fails$9(function () {
    // https://bugzilla.mozilla.org/show_bug.cgi?id=773687
    var re = $RegExp$2('^r', 'gy');
    re.lastIndex = 2;
    return re.exec('str') != null;
  });

  var regexpStickyHelpers = {
    BROKEN_CARET: BROKEN_CARET,
    MISSED_STICKY: MISSED_STICKY,
    UNSUPPORTED_Y: UNSUPPORTED_Y$2
  };

  var fails$8 = fails$n;
  var global$3 = global$e;

  // babel-minify and Closure Compiler transpiles RegExp('.', 's') -> /./s and it causes SyntaxError
  var $RegExp$1 = global$3.RegExp;

  var regexpUnsupportedDotAll = fails$8(function () {
    var re = $RegExp$1('.', 's');
    return !(re.dotAll && re.exec('\n') && re.flags === 's');
  });

  var fails$7 = fails$n;
  var global$2 = global$e;

  // babel-minify and Closure Compiler transpiles RegExp('(?<a>b)', 'g') -> /(?<a>b)/g and it causes SyntaxError
  var $RegExp = global$2.RegExp;

  var regexpUnsupportedNcg = fails$7(function () {
    var re = $RegExp('(?<a>b)', 'g');
    return re.exec('b').groups.a !== 'b' ||
      'b'.replace(re, '$<a>c') !== 'bc';
  });

  /* eslint-disable regexp/no-empty-capturing-group, regexp/no-empty-group, regexp/no-lazy-ends -- testing */
  /* eslint-disable regexp/no-useless-quantifier -- testing */
  var call$5 = functionCall;
  var uncurryThis$7 = functionUncurryThis;
  var toString$4 = toString$7;
  var regexpFlags = regexpFlags$1;
  var stickyHelpers$1 = regexpStickyHelpers;
  var shared = shared$4.exports;
  var create = objectCreate;
  var getInternalState = internalState.get;
  var UNSUPPORTED_DOT_ALL = regexpUnsupportedDotAll;
  var UNSUPPORTED_NCG = regexpUnsupportedNcg;

  var nativeReplace = shared('native-string-replace', String.prototype.replace);
  var nativeExec = RegExp.prototype.exec;
  var patchedExec = nativeExec;
  var charAt$3 = uncurryThis$7(''.charAt);
  var indexOf = uncurryThis$7(''.indexOf);
  var replace$1 = uncurryThis$7(''.replace);
  var stringSlice$4 = uncurryThis$7(''.slice);

  var UPDATES_LAST_INDEX_WRONG = (function () {
    var re1 = /a/;
    var re2 = /b*/g;
    call$5(nativeExec, re1, 'a');
    call$5(nativeExec, re2, 'a');
    return re1.lastIndex !== 0 || re2.lastIndex !== 0;
  })();

  var UNSUPPORTED_Y$1 = stickyHelpers$1.BROKEN_CARET;

  // nonparticipating capturing group, copied from es5-shim's String#split patch.
  var NPCG_INCLUDED = /()??/.exec('')[1] !== undefined;

  var PATCH = UPDATES_LAST_INDEX_WRONG || NPCG_INCLUDED || UNSUPPORTED_Y$1 || UNSUPPORTED_DOT_ALL || UNSUPPORTED_NCG;

  if (PATCH) {
    patchedExec = function exec(string) {
      var re = this;
      var state = getInternalState(re);
      var str = toString$4(string);
      var raw = state.raw;
      var result, reCopy, lastIndex, match, i, object, group;

      if (raw) {
        raw.lastIndex = re.lastIndex;
        result = call$5(patchedExec, raw, str);
        re.lastIndex = raw.lastIndex;
        return result;
      }

      var groups = state.groups;
      var sticky = UNSUPPORTED_Y$1 && re.sticky;
      var flags = call$5(regexpFlags, re);
      var source = re.source;
      var charsAdded = 0;
      var strCopy = str;

      if (sticky) {
        flags = replace$1(flags, 'y', '');
        if (indexOf(flags, 'g') === -1) {
          flags += 'g';
        }

        strCopy = stringSlice$4(str, re.lastIndex);
        // Support anchored sticky behavior.
        if (re.lastIndex > 0 && (!re.multiline || re.multiline && charAt$3(str, re.lastIndex - 1) !== '\n')) {
          source = '(?: ' + source + ')';
          strCopy = ' ' + strCopy;
          charsAdded++;
        }
        // ^(? + rx + ) is needed, in combination with some str slicing, to
        // simulate the 'y' flag.
        reCopy = new RegExp('^(?:' + source + ')', flags);
      }

      if (NPCG_INCLUDED) {
        reCopy = new RegExp('^' + source + '$(?!\\s)', flags);
      }
      if (UPDATES_LAST_INDEX_WRONG) lastIndex = re.lastIndex;

      match = call$5(nativeExec, sticky ? reCopy : re, strCopy);

      if (sticky) {
        if (match) {
          match.input = stringSlice$4(match.input, charsAdded);
          match[0] = stringSlice$4(match[0], charsAdded);
          match.index = re.lastIndex;
          re.lastIndex += match[0].length;
        } else re.lastIndex = 0;
      } else if (UPDATES_LAST_INDEX_WRONG && match) {
        re.lastIndex = re.global ? match.index + match[0].length : lastIndex;
      }
      if (NPCG_INCLUDED && match && match.length > 1) {
        // Fix browsers whose `exec` methods don't consistently return `undefined`
        // for NPCG, like IE8. NOTE: This doesn' work for /(.?)?/
        call$5(nativeReplace, match[0], reCopy, function () {
          for (i = 1; i < arguments.length - 2; i++) {
            if (arguments[i] === undefined) match[i] = undefined;
          }
        });
      }

      if (match && groups) {
        match.groups = object = create(null);
        for (i = 0; i < groups.length; i++) {
          group = groups[i];
          object[group[0]] = match[group[1]];
        }
      }

      return match;
    };
  }

  var regexpExec$3 = patchedExec;

  var $$6 = _export;
  var exec$1 = regexpExec$3;

  // `RegExp.prototype.exec` method
  // https://tc39.es/ecma262/#sec-regexp.prototype.exec
  $$6({ target: 'RegExp', proto: true, forced: /./.exec !== exec$1 }, {
    exec: exec$1
  });

  var NATIVE_BIND = functionBindNative;

  var FunctionPrototype = Function.prototype;
  var apply$2 = FunctionPrototype.apply;
  var call$4 = FunctionPrototype.call;

  // eslint-disable-next-line es-x/no-reflect -- safe
  var functionApply = typeof Reflect == 'object' && Reflect.apply || (NATIVE_BIND ? call$4.bind(apply$2) : function () {
    return call$4.apply(apply$2, arguments);
  });

  // TODO: Remove from `core-js@4` since it's moved to entry points

  var uncurryThis$6 = functionUncurryThis;
  var defineBuiltIn = defineBuiltIn$4;
  var regexpExec$2 = regexpExec$3;
  var fails$6 = fails$n;
  var wellKnownSymbol$5 = wellKnownSymbol$b;
  var createNonEnumerableProperty$1 = createNonEnumerableProperty$5;

  var SPECIES$2 = wellKnownSymbol$5('species');
  var RegExpPrototype = RegExp.prototype;

  var fixRegexpWellKnownSymbolLogic = function (KEY, exec, FORCED, SHAM) {
    var SYMBOL = wellKnownSymbol$5(KEY);

    var DELEGATES_TO_SYMBOL = !fails$6(function () {
      // String methods call symbol-named RegEp methods
      var O = {};
      O[SYMBOL] = function () { return 7; };
      return ''[KEY](O) != 7;
    });

    var DELEGATES_TO_EXEC = DELEGATES_TO_SYMBOL && !fails$6(function () {
      // Symbol-named RegExp methods call .exec
      var execCalled = false;
      var re = /a/;

      if (KEY === 'split') {
        // We can't use real regex here since it causes deoptimization
        // and serious performance degradation in V8
        // https://github.com/zloirock/core-js/issues/306
        re = {};
        // RegExp[@@split] doesn't call the regex's exec method, but first creates
        // a new one. We need to return the patched regex when creating the new one.
        re.constructor = {};
        re.constructor[SPECIES$2] = function () { return re; };
        re.flags = '';
        re[SYMBOL] = /./[SYMBOL];
      }

      re.exec = function () { execCalled = true; return null; };

      re[SYMBOL]('');
      return !execCalled;
    });

    if (
      !DELEGATES_TO_SYMBOL ||
      !DELEGATES_TO_EXEC ||
      FORCED
    ) {
      var uncurriedNativeRegExpMethod = uncurryThis$6(/./[SYMBOL]);
      var methods = exec(SYMBOL, ''[KEY], function (nativeMethod, regexp, str, arg2, forceStringMethod) {
        var uncurriedNativeMethod = uncurryThis$6(nativeMethod);
        var $exec = regexp.exec;
        if ($exec === regexpExec$2 || $exec === RegExpPrototype.exec) {
          if (DELEGATES_TO_SYMBOL && !forceStringMethod) {
            // The native String method already delegates to @@method (this
            // polyfilled function), leasing to infinite recursion.
            // We avoid it by directly calling the native @@method method.
            return { done: true, value: uncurriedNativeRegExpMethod(regexp, str, arg2) };
          }
          return { done: true, value: uncurriedNativeMethod(str, regexp, arg2) };
        }
        return { done: false };
      });

      defineBuiltIn(String.prototype, KEY, methods[0]);
      defineBuiltIn(RegExpPrototype, SYMBOL, methods[1]);
    }

    if (SHAM) createNonEnumerableProperty$1(RegExpPrototype[SYMBOL], 'sham', true);
  };

  var uncurryThis$5 = functionUncurryThis;
  var toIntegerOrInfinity$1 = toIntegerOrInfinity$4;
  var toString$3 = toString$7;
  var requireObjectCoercible$3 = requireObjectCoercible$7;

  var charAt$2 = uncurryThis$5(''.charAt);
  var charCodeAt = uncurryThis$5(''.charCodeAt);
  var stringSlice$3 = uncurryThis$5(''.slice);

  var createMethod = function (CONVERT_TO_STRING) {
    return function ($this, pos) {
      var S = toString$3(requireObjectCoercible$3($this));
      var position = toIntegerOrInfinity$1(pos);
      var size = S.length;
      var first, second;
      if (position < 0 || position >= size) return CONVERT_TO_STRING ? '' : undefined;
      first = charCodeAt(S, position);
      return first < 0xD800 || first > 0xDBFF || position + 1 === size
        || (second = charCodeAt(S, position + 1)) < 0xDC00 || second > 0xDFFF
          ? CONVERT_TO_STRING
            ? charAt$2(S, position)
            : first
          : CONVERT_TO_STRING
            ? stringSlice$3(S, position, position + 2)
            : (first - 0xD800 << 10) + (second - 0xDC00) + 0x10000;
    };
  };

  var stringMultibyte = {
    // `String.prototype.codePointAt` method
    // https://tc39.es/ecma262/#sec-string.prototype.codepointat
    codeAt: createMethod(false),
    // `String.prototype.at` method
    // https://github.com/mathiasbynens/String.prototype.at
    charAt: createMethod(true)
  };

  var charAt$1 = stringMultibyte.charAt;

  // `AdvanceStringIndex` abstract operation
  // https://tc39.es/ecma262/#sec-advancestringindex
  var advanceStringIndex$3 = function (S, index, unicode) {
    return index + (unicode ? charAt$1(S, index).length : 1);
  };

  var uncurryThis$4 = functionUncurryThis;
  var toObject$2 = toObject$6;

  var floor = Math.floor;
  var charAt = uncurryThis$4(''.charAt);
  var replace = uncurryThis$4(''.replace);
  var stringSlice$2 = uncurryThis$4(''.slice);
  var SUBSTITUTION_SYMBOLS = /\$([$&'`]|\d{1,2}|<[^>]*>)/g;
  var SUBSTITUTION_SYMBOLS_NO_NAMED = /\$([$&'`]|\d{1,2})/g;

  // `GetSubstitution` abstract operation
  // https://tc39.es/ecma262/#sec-getsubstitution
  var getSubstitution$1 = function (matched, str, position, captures, namedCaptures, replacement) {
    var tailPos = position + matched.length;
    var m = captures.length;
    var symbols = SUBSTITUTION_SYMBOLS_NO_NAMED;
    if (namedCaptures !== undefined) {
      namedCaptures = toObject$2(namedCaptures);
      symbols = SUBSTITUTION_SYMBOLS;
    }
    return replace(replacement, symbols, function (match, ch) {
      var capture;
      switch (charAt(ch, 0)) {
        case '$': return '$';
        case '&': return matched;
        case '`': return stringSlice$2(str, 0, position);
        case "'": return stringSlice$2(str, tailPos);
        case '<':
          capture = namedCaptures[stringSlice$2(ch, 1, -1)];
          break;
        default: // \d\d?
          var n = +ch;
          if (n === 0) return match;
          if (n > m) {
            var f = floor(n / 10);
            if (f === 0) return match;
            if (f <= m) return captures[f - 1] === undefined ? charAt(ch, 1) : captures[f - 1] + charAt(ch, 1);
            return match;
          }
          capture = captures[n - 1];
      }
      return capture === undefined ? '' : capture;
    });
  };

  var call$3 = functionCall;
  var anObject$4 = anObject$b;
  var isCallable$1 = isCallable$e;
  var classof$1 = classofRaw$1;
  var regexpExec$1 = regexpExec$3;

  var $TypeError$2 = TypeError;

  // `RegExpExec` abstract operation
  // https://tc39.es/ecma262/#sec-regexpexec
  var regexpExecAbstract = function (R, S) {
    var exec = R.exec;
    if (isCallable$1(exec)) {
      var result = call$3(exec, R, S);
      if (result !== null) anObject$4(result);
      return result;
    }
    if (classof$1(R) === 'RegExp') return call$3(regexpExec$1, R, S);
    throw $TypeError$2('RegExp#exec called on incompatible receiver');
  };

  var apply$1 = functionApply;
  var call$2 = functionCall;
  var uncurryThis$3 = functionUncurryThis;
  var fixRegExpWellKnownSymbolLogic$2 = fixRegexpWellKnownSymbolLogic;
  var fails$5 = fails$n;
  var anObject$3 = anObject$b;
  var isCallable = isCallable$e;
  var toIntegerOrInfinity = toIntegerOrInfinity$4;
  var toLength$2 = toLength$4;
  var toString$2 = toString$7;
  var requireObjectCoercible$2 = requireObjectCoercible$7;
  var advanceStringIndex$2 = advanceStringIndex$3;
  var getMethod$2 = getMethod$4;
  var getSubstitution = getSubstitution$1;
  var regExpExec$1 = regexpExecAbstract;
  var wellKnownSymbol$4 = wellKnownSymbol$b;

  var REPLACE = wellKnownSymbol$4('replace');
  var max = Math.max;
  var min$1 = Math.min;
  var concat = uncurryThis$3([].concat);
  var push$1 = uncurryThis$3([].push);
  var stringIndexOf = uncurryThis$3(''.indexOf);
  var stringSlice$1 = uncurryThis$3(''.slice);

  var maybeToString = function (it) {
    return it === undefined ? it : String(it);
  };

  // IE <= 11 replaces $0 with the whole match, as if it was $&
  // https://stackoverflow.com/questions/6024666/getting-ie-to-replace-a-regex-with-the-literal-string-0
  var REPLACE_KEEPS_$0 = (function () {
    // eslint-disable-next-line regexp/prefer-escape-replacement-dollar-char -- required for testing
    return 'a'.replace(/./, '$0') === '$0';
  })();

  // Safari <= 13.0.3(?) substitutes nth capture where n>m with an empty string
  var REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE = (function () {
    if (/./[REPLACE]) {
      return /./[REPLACE]('a', '$0') === '';
    }
    return false;
  })();

  var REPLACE_SUPPORTS_NAMED_GROUPS = !fails$5(function () {
    var re = /./;
    re.exec = function () {
      var result = [];
      result.groups = { a: '7' };
      return result;
    };
    // eslint-disable-next-line regexp/no-useless-dollar-replacements -- false positive
    return ''.replace(re, '$<a>') !== '7';
  });

  // @@replace logic
  fixRegExpWellKnownSymbolLogic$2('replace', function (_, nativeReplace, maybeCallNative) {
    var UNSAFE_SUBSTITUTE = REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE ? '$' : '$0';

    return [
      // `String.prototype.replace` method
      // https://tc39.es/ecma262/#sec-string.prototype.replace
      function replace(searchValue, replaceValue) {
        var O = requireObjectCoercible$2(this);
        var replacer = searchValue == undefined ? undefined : getMethod$2(searchValue, REPLACE);
        return replacer
          ? call$2(replacer, searchValue, O, replaceValue)
          : call$2(nativeReplace, toString$2(O), searchValue, replaceValue);
      },
      // `RegExp.prototype[@@replace]` method
      // https://tc39.es/ecma262/#sec-regexp.prototype-@@replace
      function (string, replaceValue) {
        var rx = anObject$3(this);
        var S = toString$2(string);

        if (
          typeof replaceValue == 'string' &&
          stringIndexOf(replaceValue, UNSAFE_SUBSTITUTE) === -1 &&
          stringIndexOf(replaceValue, '$<') === -1
        ) {
          var res = maybeCallNative(nativeReplace, rx, S, replaceValue);
          if (res.done) return res.value;
        }

        var functionalReplace = isCallable(replaceValue);
        if (!functionalReplace) replaceValue = toString$2(replaceValue);

        var global = rx.global;
        if (global) {
          var fullUnicode = rx.unicode;
          rx.lastIndex = 0;
        }
        var results = [];
        while (true) {
          var result = regExpExec$1(rx, S);
          if (result === null) break;

          push$1(results, result);
          if (!global) break;

          var matchStr = toString$2(result[0]);
          if (matchStr === '') rx.lastIndex = advanceStringIndex$2(S, toLength$2(rx.lastIndex), fullUnicode);
        }

        var accumulatedResult = '';
        var nextSourcePosition = 0;
        for (var i = 0; i < results.length; i++) {
          result = results[i];

          var matched = toString$2(result[0]);
          var position = max(min$1(toIntegerOrInfinity(result.index), S.length), 0);
          var captures = [];
          // NOTE: This is equivalent to
          //   captures = result.slice(1).map(maybeToString)
          // but for some reason `nativeSlice.call(result, 1, result.length)` (called in
          // the slice polyfill when slicing native arrays) "doesn't work" in safari 9 and
          // causes a crash (https://pastebin.com/N21QzeQA) when trying to debug it.
          for (var j = 1; j < result.length; j++) push$1(captures, maybeToString(result[j]));
          var namedCaptures = result.groups;
          if (functionalReplace) {
            var replacerArgs = concat([matched], captures, position, S);
            if (namedCaptures !== undefined) push$1(replacerArgs, namedCaptures);
            var replacement = toString$2(apply$1(replaceValue, undefined, replacerArgs));
          } else {
            replacement = getSubstitution(matched, S, position, captures, namedCaptures, replaceValue);
          }
          if (position >= nextSourcePosition) {
            accumulatedResult += stringSlice$1(S, nextSourcePosition, position) + replacement;
            nextSourcePosition = position + matched.length;
          }
        }
        return accumulatedResult + stringSlice$1(S, nextSourcePosition);
      }
    ];
  }, !REPLACE_SUPPORTS_NAMED_GROUPS || !REPLACE_KEEPS_$0 || REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE);

  var $TypeError$1 = TypeError;
  var MAX_SAFE_INTEGER = 0x1FFFFFFFFFFFFF; // 2 ** 53 - 1 == 9007199254740991

  var doesNotExceedSafeInteger$1 = function (it) {
    if (it > MAX_SAFE_INTEGER) throw $TypeError$1('Maximum allowed index exceeded');
    return it;
  };

  var fails$4 = fails$n;
  var wellKnownSymbol$3 = wellKnownSymbol$b;
  var V8_VERSION$1 = engineV8Version;

  var SPECIES$1 = wellKnownSymbol$3('species');

  var arrayMethodHasSpeciesSupport$2 = function (METHOD_NAME) {
    // We can't use this feature detection in V8 since it causes
    // deoptimization and serious performance degradation
    // https://github.com/zloirock/core-js/issues/677
    return V8_VERSION$1 >= 51 || !fails$4(function () {
      var array = [];
      var constructor = array.constructor = {};
      constructor[SPECIES$1] = function () {
        return { foo: 1 };
      };
      return array[METHOD_NAME](Boolean).foo !== 1;
    });
  };

  var $$5 = _export;
  var fails$3 = fails$n;
  var isArray = isArray$2;
  var isObject$1 = isObject$8;
  var toObject$1 = toObject$6;
  var lengthOfArrayLike = lengthOfArrayLike$5;
  var doesNotExceedSafeInteger = doesNotExceedSafeInteger$1;
  var createProperty = createProperty$2;
  var arraySpeciesCreate = arraySpeciesCreate$2;
  var arrayMethodHasSpeciesSupport$1 = arrayMethodHasSpeciesSupport$2;
  var wellKnownSymbol$2 = wellKnownSymbol$b;
  var V8_VERSION = engineV8Version;

  var IS_CONCAT_SPREADABLE = wellKnownSymbol$2('isConcatSpreadable');

  // We can't use this feature detection in V8 since it causes
  // deoptimization and serious performance degradation
  // https://github.com/zloirock/core-js/issues/679
  var IS_CONCAT_SPREADABLE_SUPPORT = V8_VERSION >= 51 || !fails$3(function () {
    var array = [];
    array[IS_CONCAT_SPREADABLE] = false;
    return array.concat()[0] !== array;
  });

  var SPECIES_SUPPORT = arrayMethodHasSpeciesSupport$1('concat');

  var isConcatSpreadable = function (O) {
    if (!isObject$1(O)) return false;
    var spreadable = O[IS_CONCAT_SPREADABLE];
    return spreadable !== undefined ? !!spreadable : isArray(O);
  };

  var FORCED = !IS_CONCAT_SPREADABLE_SUPPORT || !SPECIES_SUPPORT;

  // `Array.prototype.concat` method
  // https://tc39.es/ecma262/#sec-array.prototype.concat
  // with adding support of @@isConcatSpreadable and @@species
  $$5({ target: 'Array', proto: true, arity: 1, forced: FORCED }, {
    // eslint-disable-next-line no-unused-vars -- required for `.length`
    concat: function concat(arg) {
      var O = toObject$1(this);
      var A = arraySpeciesCreate(O, 0);
      var n = 0;
      var i, k, length, len, E;
      for (i = -1, length = arguments.length; i < length; i++) {
        E = i === -1 ? O : arguments[i];
        if (isConcatSpreadable(E)) {
          len = lengthOfArrayLike(E);
          doesNotExceedSafeInteger(n + len);
          for (k = 0; k < len; k++, n++) if (k in E) createProperty(A, n, E[k]);
        } else {
          doesNotExceedSafeInteger(n + 1);
          createProperty(A, n++, E);
        }
      }
      A.length = n;
      return A;
    }
  });

  var $$4 = _export;
  var $filter = arrayIteration.filter;
  var arrayMethodHasSpeciesSupport = arrayMethodHasSpeciesSupport$2;

  var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('filter');

  // `Array.prototype.filter` method
  // https://tc39.es/ecma262/#sec-array.prototype.filter
  // with adding support of @@species
  $$4({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
    filter: function filter(callbackfn /* , thisArg */) {
      return $filter(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
    }
  });

  var call$1 = functionCall;
  var fixRegExpWellKnownSymbolLogic$1 = fixRegexpWellKnownSymbolLogic;
  var anObject$2 = anObject$b;
  var toLength$1 = toLength$4;
  var toString$1 = toString$7;
  var requireObjectCoercible$1 = requireObjectCoercible$7;
  var getMethod$1 = getMethod$4;
  var advanceStringIndex$1 = advanceStringIndex$3;
  var regExpExec = regexpExecAbstract;

  // @@match logic
  fixRegExpWellKnownSymbolLogic$1('match', function (MATCH, nativeMatch, maybeCallNative) {
    return [
      // `String.prototype.match` method
      // https://tc39.es/ecma262/#sec-string.prototype.match
      function match(regexp) {
        var O = requireObjectCoercible$1(this);
        var matcher = regexp == undefined ? undefined : getMethod$1(regexp, MATCH);
        return matcher ? call$1(matcher, regexp, O) : new RegExp(regexp)[MATCH](toString$1(O));
      },
      // `RegExp.prototype[@@match]` method
      // https://tc39.es/ecma262/#sec-regexp.prototype-@@match
      function (string) {
        var rx = anObject$2(this);
        var S = toString$1(string);
        var res = maybeCallNative(nativeMatch, rx, S);

        if (res.done) return res.value;

        if (!rx.global) return regExpExec(rx, S);

        var fullUnicode = rx.unicode;
        rx.lastIndex = 0;
        var A = [];
        var n = 0;
        var result;
        while ((result = regExpExec(rx, S)) !== null) {
          var matchStr = toString$1(result[0]);
          A[n] = matchStr;
          if (matchStr === '') rx.lastIndex = advanceStringIndex$1(S, toLength$1(rx.lastIndex), fullUnicode);
          n++;
        }
        return n === 0 ? null : A;
      }
    ];
  });

  var isObject = isObject$8;
  var classof = classofRaw$1;
  var wellKnownSymbol$1 = wellKnownSymbol$b;

  var MATCH = wellKnownSymbol$1('match');

  // `IsRegExp` abstract operation
  // https://tc39.es/ecma262/#sec-isregexp
  var isRegexp = function (it) {
    var isRegExp;
    return isObject(it) && ((isRegExp = it[MATCH]) !== undefined ? !!isRegExp : classof(it) == 'RegExp');
  };

  var isConstructor = isConstructor$2;
  var tryToString = tryToString$3;

  var $TypeError = TypeError;

  // `Assert: IsConstructor(argument) is true`
  var aConstructor$1 = function (argument) {
    if (isConstructor(argument)) return argument;
    throw $TypeError(tryToString(argument) + ' is not a constructor');
  };

  var anObject$1 = anObject$b;
  var aConstructor = aConstructor$1;
  var wellKnownSymbol = wellKnownSymbol$b;

  var SPECIES = wellKnownSymbol('species');

  // `SpeciesConstructor` abstract operation
  // https://tc39.es/ecma262/#sec-speciesconstructor
  var speciesConstructor$1 = function (O, defaultConstructor) {
    var C = anObject$1(O).constructor;
    var S;
    return C === undefined || (S = anObject$1(C)[SPECIES]) == undefined ? defaultConstructor : aConstructor(S);
  };

  var apply = functionApply;
  var call = functionCall;
  var uncurryThis$2 = functionUncurryThis;
  var fixRegExpWellKnownSymbolLogic = fixRegexpWellKnownSymbolLogic;
  var isRegExp = isRegexp;
  var anObject = anObject$b;
  var requireObjectCoercible = requireObjectCoercible$7;
  var speciesConstructor = speciesConstructor$1;
  var advanceStringIndex = advanceStringIndex$3;
  var toLength = toLength$4;
  var toString = toString$7;
  var getMethod = getMethod$4;
  var arraySlice = arraySliceSimple;
  var callRegExpExec = regexpExecAbstract;
  var regexpExec = regexpExec$3;
  var stickyHelpers = regexpStickyHelpers;
  var fails$2 = fails$n;

  var UNSUPPORTED_Y = stickyHelpers.UNSUPPORTED_Y;
  var MAX_UINT32 = 0xFFFFFFFF;
  var min = Math.min;
  var $push = [].push;
  var exec = uncurryThis$2(/./.exec);
  var push = uncurryThis$2($push);
  var stringSlice = uncurryThis$2(''.slice);

  // Chrome 51 has a buggy "split" implementation when RegExp#exec !== nativeExec
  // Weex JS has frozen built-in prototypes, so use try / catch wrapper
  var SPLIT_WORKS_WITH_OVERWRITTEN_EXEC = !fails$2(function () {
    // eslint-disable-next-line regexp/no-empty-group -- required for testing
    var re = /(?:)/;
    var originalExec = re.exec;
    re.exec = function () { return originalExec.apply(this, arguments); };
    var result = 'ab'.split(re);
    return result.length !== 2 || result[0] !== 'a' || result[1] !== 'b';
  });

  // @@split logic
  fixRegExpWellKnownSymbolLogic('split', function (SPLIT, nativeSplit, maybeCallNative) {
    var internalSplit;
    if (
      'abbc'.split(/(b)*/)[1] == 'c' ||
      // eslint-disable-next-line regexp/no-empty-group -- required for testing
      'test'.split(/(?:)/, -1).length != 4 ||
      'ab'.split(/(?:ab)*/).length != 2 ||
      '.'.split(/(.?)(.?)/).length != 4 ||
      // eslint-disable-next-line regexp/no-empty-capturing-group, regexp/no-empty-group -- required for testing
      '.'.split(/()()/).length > 1 ||
      ''.split(/.?/).length
    ) {
      // based on es5-shim implementation, need to rework it
      internalSplit = function (separator, limit) {
        var string = toString(requireObjectCoercible(this));
        var lim = limit === undefined ? MAX_UINT32 : limit >>> 0;
        if (lim === 0) return [];
        if (separator === undefined) return [string];
        // If `separator` is not a regex, use native split
        if (!isRegExp(separator)) {
          return call(nativeSplit, string, separator, lim);
        }
        var output = [];
        var flags = (separator.ignoreCase ? 'i' : '') +
                    (separator.multiline ? 'm' : '') +
                    (separator.unicode ? 'u' : '') +
                    (separator.sticky ? 'y' : '');
        var lastLastIndex = 0;
        // Make `global` and avoid `lastIndex` issues by working with a copy
        var separatorCopy = new RegExp(separator.source, flags + 'g');
        var match, lastIndex, lastLength;
        while (match = call(regexpExec, separatorCopy, string)) {
          lastIndex = separatorCopy.lastIndex;
          if (lastIndex > lastLastIndex) {
            push(output, stringSlice(string, lastLastIndex, match.index));
            if (match.length > 1 && match.index < string.length) apply($push, output, arraySlice(match, 1));
            lastLength = match[0].length;
            lastLastIndex = lastIndex;
            if (output.length >= lim) break;
          }
          if (separatorCopy.lastIndex === match.index) separatorCopy.lastIndex++; // Avoid an infinite loop
        }
        if (lastLastIndex === string.length) {
          if (lastLength || !exec(separatorCopy, '')) push(output, '');
        } else push(output, stringSlice(string, lastLastIndex));
        return output.length > lim ? arraySlice(output, 0, lim) : output;
      };
    // Chakra, V8
    } else if ('0'.split(undefined, 0).length) {
      internalSplit = function (separator, limit) {
        return separator === undefined && limit === 0 ? [] : call(nativeSplit, this, separator, limit);
      };
    } else internalSplit = nativeSplit;

    return [
      // `String.prototype.split` method
      // https://tc39.es/ecma262/#sec-string.prototype.split
      function split(separator, limit) {
        var O = requireObjectCoercible(this);
        var splitter = separator == undefined ? undefined : getMethod(separator, SPLIT);
        return splitter
          ? call(splitter, separator, O, limit)
          : call(internalSplit, toString(O), separator, limit);
      },
      // `RegExp.prototype[@@split]` method
      // https://tc39.es/ecma262/#sec-regexp.prototype-@@split
      //
      // NOTE: This cannot be properly polyfilled in engines that don't support
      // the 'y' flag.
      function (string, limit) {
        var rx = anObject(this);
        var S = toString(string);
        var res = maybeCallNative(internalSplit, rx, S, limit, internalSplit !== nativeSplit);

        if (res.done) return res.value;

        var C = speciesConstructor(rx, RegExp);

        var unicodeMatching = rx.unicode;
        var flags = (rx.ignoreCase ? 'i' : '') +
                    (rx.multiline ? 'm' : '') +
                    (rx.unicode ? 'u' : '') +
                    (UNSUPPORTED_Y ? 'g' : 'y');

        // ^(? + rx + ) is needed, in combination with some S slicing, to
        // simulate the 'y' flag.
        var splitter = new C(UNSUPPORTED_Y ? '^(?:' + rx.source + ')' : rx, flags);
        var lim = limit === undefined ? MAX_UINT32 : limit >>> 0;
        if (lim === 0) return [];
        if (S.length === 0) return callRegExpExec(splitter, S) === null ? [S] : [];
        var p = 0;
        var q = 0;
        var A = [];
        while (q < S.length) {
          splitter.lastIndex = UNSUPPORTED_Y ? 0 : q;
          var z = callRegExpExec(splitter, UNSUPPORTED_Y ? stringSlice(S, q) : S);
          var e;
          if (
            z === null ||
            (e = min(toLength(splitter.lastIndex + (UNSUPPORTED_Y ? q : 0)), S.length)) === p
          ) {
            q = advanceStringIndex(S, q, unicodeMatching);
          } else {
            push(A, stringSlice(S, p, q));
            if (A.length === lim) return A;
            for (var i = 1; i <= z.length - 1; i++) {
              push(A, z[i]);
              if (A.length === lim) return A;
            }
            q = p = e;
          }
        }
        push(A, stringSlice(S, p));
        return A;
      }
    ];
  }, !SPLIT_WORKS_WITH_OVERWRITTEN_EXEC, UNSUPPORTED_Y);

  var $$3 = _export;
  var $includes = arrayIncludes.includes;
  var fails$1 = fails$n;
  var addToUnscopables = addToUnscopables$2;

  // FF99+ bug
  var BROKEN_ON_SPARSE = fails$1(function () {
    return !Array(1).includes();
  });

  // `Array.prototype.includes` method
  // https://tc39.es/ecma262/#sec-array.prototype.includes
  $$3({ target: 'Array', proto: true, forced: BROKEN_ON_SPARSE }, {
    includes: function includes(el /* , fromIndex = 0 */) {
      return $includes(this, el, arguments.length > 1 ? arguments[1] : undefined);
    }
  });

  // https://tc39.es/ecma262/#sec-array.prototype-@@unscopables
  addToUnscopables('includes');

  // iterable DOM collections
  // flag - `iterable` interface - 'entries', 'keys', 'values', 'forEach' methods
  var domIterables = {
    CSSRuleList: 0,
    CSSStyleDeclaration: 0,
    CSSValueList: 0,
    ClientRectList: 0,
    DOMRectList: 0,
    DOMStringList: 0,
    DOMTokenList: 1,
    DataTransferItemList: 0,
    FileList: 0,
    HTMLAllCollection: 0,
    HTMLCollection: 0,
    HTMLFormElement: 0,
    HTMLSelectElement: 0,
    MediaList: 0,
    MimeTypeArray: 0,
    NamedNodeMap: 0,
    NodeList: 1,
    PaintRequestList: 0,
    Plugin: 0,
    PluginArray: 0,
    SVGLengthList: 0,
    SVGNumberList: 0,
    SVGPathSegList: 0,
    SVGPointList: 0,
    SVGStringList: 0,
    SVGTransformList: 0,
    SourceBufferList: 0,
    StyleSheetList: 0,
    TextTrackCueList: 0,
    TextTrackList: 0,
    TouchList: 0
  };

  // in old WebKit versions, `element.classList` is not an instance of global `DOMTokenList`
  var documentCreateElement = documentCreateElement$2;

  var classList = documentCreateElement('span').classList;
  var DOMTokenListPrototype$1 = classList && classList.constructor && classList.constructor.prototype;

  var domTokenListPrototype = DOMTokenListPrototype$1 === Object.prototype ? undefined : DOMTokenListPrototype$1;

  var $forEach = arrayIteration.forEach;
  var arrayMethodIsStrict$2 = arrayMethodIsStrict$4;

  var STRICT_METHOD$2 = arrayMethodIsStrict$2('forEach');

  // `Array.prototype.forEach` method implementation
  // https://tc39.es/ecma262/#sec-array.prototype.foreach
  var arrayForEach = !STRICT_METHOD$2 ? function forEach(callbackfn /* , thisArg */) {
    return $forEach(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  // eslint-disable-next-line es-x/no-array-prototype-foreach -- safe
  } : [].forEach;

  var global$1 = global$e;
  var DOMIterables = domIterables;
  var DOMTokenListPrototype = domTokenListPrototype;
  var forEach = arrayForEach;
  var createNonEnumerableProperty = createNonEnumerableProperty$5;

  var handlePrototype = function (CollectionPrototype) {
    // some Chrome versions have non-configurable methods on DOMTokenList
    if (CollectionPrototype && CollectionPrototype.forEach !== forEach) try {
      createNonEnumerableProperty(CollectionPrototype, 'forEach', forEach);
    } catch (error) {
      CollectionPrototype.forEach = forEach;
    }
  };

  for (var COLLECTION_NAME in DOMIterables) {
    if (DOMIterables[COLLECTION_NAME]) {
      handlePrototype(global$1[COLLECTION_NAME] && global$1[COLLECTION_NAME].prototype);
    }
  }

  handlePrototype(DOMTokenListPrototype);

  var $$2 = _export;
  var toObject = toObject$6;
  var nativeKeys = objectKeys$1;
  var fails = fails$n;

  var FAILS_ON_PRIMITIVES = fails(function () { nativeKeys(1); });

  // `Object.keys` method
  // https://tc39.es/ecma262/#sec-object.keys
  $$2({ target: 'Object', stat: true, forced: FAILS_ON_PRIMITIVES }, {
    keys: function keys(it) {
      return nativeKeys(toObject(it));
    }
  });

  var $$1 = _export;
  var uncurryThis$1 = functionUncurryThis;
  var IndexedObject = indexedObject;
  var toIndexedObject = toIndexedObject$5;
  var arrayMethodIsStrict$1 = arrayMethodIsStrict$4;

  var un$Join = uncurryThis$1([].join);

  var ES3_STRINGS = IndexedObject != Object;
  var STRICT_METHOD$1 = arrayMethodIsStrict$1('join', ',');

  // `Array.prototype.join` method
  // https://tc39.es/ecma262/#sec-array.prototype.join
  $$1({ target: 'Array', proto: true, forced: ES3_STRINGS || !STRICT_METHOD$1 }, {
    join: function join(separator) {
      return un$Join(toIndexedObject(this), separator === undefined ? ',' : separator);
    }
  });

  /* eslint-disable es-x/no-array-prototype-indexof -- required for testing */
  var $ = _export;
  var uncurryThis = functionUncurryThis;
  var $IndexOf = arrayIncludes.indexOf;
  var arrayMethodIsStrict = arrayMethodIsStrict$4;

  var un$IndexOf = uncurryThis([].indexOf);

  var NEGATIVE_ZERO = !!un$IndexOf && 1 / un$IndexOf([1], 1, -0) < 0;
  var STRICT_METHOD = arrayMethodIsStrict('indexOf');

  // `Array.prototype.indexOf` method
  // https://tc39.es/ecma262/#sec-array.prototype.indexof
  $({ target: 'Array', proto: true, forced: NEGATIVE_ZERO || !STRICT_METHOD }, {
    indexOf: function indexOf(searchElement /* , fromIndex = 0 */) {
      var fromIndex = arguments.length > 1 ? arguments[1] : undefined;
      return NEGATIVE_ZERO
        // convert -0 to +0
        ? un$IndexOf(this, searchElement, fromIndex) || 0
        : $IndexOf(this, searchElement, fromIndex);
    }
  });

  /* eslint-disable no-use-before-define */

  var Utils = $__default["default"].fn.bootstrapTable.utils;
  var searchControls = 'select, input:not([type="checkbox"]):not([type="radio"])';
  function getInputClass(that) {
    var isSelect = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    var formControlClass = isSelect ? that.constants.classes.select : that.constants.classes.input;
    return that.options.iconSize ? Utils.sprintf('%s-%s', formControlClass, that.options.iconSize) : formControlClass;
  }
  function getOptionsFromSelectControl(selectControl) {
    return selectControl[0].options;
  }
  function getControlContainer(that) {
    if (that.options.filterControlContainer) {
      return $__default["default"]("".concat(that.options.filterControlContainer));
    }

    if (that.options.height && that._initialized) {
      return $__default["default"]('.fixed-table-header table thead');
    }

    return that.$header;
  }
  function isKeyAllowed(keyCode) {
    return $__default["default"].inArray(keyCode, [37, 38, 39, 40]) > -1;
  }
  function getSearchControls(that) {
    return getControlContainer(that).find(searchControls);
  }
  function hideUnusedSelectOptions(selectControl, uniqueValues) {
    var options = getOptionsFromSelectControl(selectControl);

    for (var i = 0; i < options.length; i++) {
      if (options[i].value !== '') {
        if (!uniqueValues.hasOwnProperty(options[i].value)) {
          selectControl.find(Utils.sprintf('option[value=\'%s\']', options[i].value)).hide();
        } else {
          selectControl.find(Utils.sprintf('option[value=\'%s\']', options[i].value)).show();
        }
      }
    }
  }
  function existOptionInSelectControl(selectControl, value) {
    var options = getOptionsFromSelectControl(selectControl);

    for (var i = 0; i < options.length; i++) {
      if (options[i].value === Utils.unescapeHTML(value)) {
        // The value is not valid to add
        return true;
      }
    } // If we get here, the value is valid to add


    return false;
  }
  function addOptionToSelectControl(selectControl, _value, text, selected, shouldCompareText) {
    var value = _value === undefined || _value === null ? '' : _value.toString().trim();
    value = Utils.removeHTML(Utils.unescapeHTML(value));
    text = Utils.removeHTML(Utils.unescapeHTML(text));

    if (existOptionInSelectControl(selectControl, value)) {
      return;
    }

    var isSelected = shouldCompareText ? value === selected || text === selected : value === selected;
    var option = new Option(text, value, false, isSelected);
    selectControl.get(0).add(option);
  }
  function sortSelectControl(selectControl, orderBy, options) {
    var $selectControl = selectControl.get(0);

    if (orderBy === 'server') {
      return;
    }

    var tmpAry = new Array();

    for (var i = 0; i < $selectControl.options.length; i++) {
      tmpAry[i] = new Array();
      tmpAry[i][0] = $selectControl.options[i].text;
      tmpAry[i][1] = $selectControl.options[i].value;
      tmpAry[i][2] = $selectControl.options[i].selected;
    }

    tmpAry.sort(function (a, b) {
      return Utils.sort(a[0], b[0], orderBy === 'desc' ? -1 : 1, options);
    });

    while ($selectControl.options.length > 0) {
      $selectControl.options[0] = null;
    }

    for (var _i = 0; _i < tmpAry.length; _i++) {
      var op = new Option(tmpAry[_i][0], tmpAry[_i][1], false, tmpAry[_i][2]);
      $selectControl.add(op);
    }
  }
  function fixHeaderCSS(_ref) {
    var $tableHeader = _ref.$tableHeader;
    $tableHeader.css('height', $tableHeader.find('table').outerHeight(true));
  }
  function getElementClass($element) {
    return $element.attr('class').replace('form-control', '').replace('form-select', '').replace('focus-temp', '').replace('search-input', '').trim();
  }
  function getCursorPosition(el) {
    if ($__default["default"](el).is('input[type=search]')) {
      var pos = 0;

      if ('selectionStart' in el) {
        pos = el.selectionStart;
      } else if ('selection' in document) {
        el.focus();
        var Sel = document.selection.createRange();
        var SelLength = document.selection.createRange().text.length;
        Sel.moveStart('character', -el.value.length);
        pos = Sel.text.length - SelLength;
      }

      return pos;
    }

    return -1;
  }
  function cacheValues(that) {
    var searchControls = getSearchControls(that);
    that._valuesFilterControl = [];
    searchControls.each(function () {
      var $field = $__default["default"](this);
      var fieldClass = getElementClass($field);

      if (that.options.height && !that.options.filterControlContainer) {
        $field = that.$el.find(".fixed-table-header .".concat(fieldClass));
      } else if (that.options.filterControlContainer) {
        $field = $__default["default"]("".concat(that.options.filterControlContainer, " .").concat(fieldClass));
      } else {
        $field = that.$el.find(".".concat(fieldClass));
      }

      that._valuesFilterControl.push({
        field: $field.closest('[data-field]').data('field'),
        value: $field.val(),
        position: getCursorPosition($field.get(0)),
        hasFocus: $field.is(':focus')
      });
    });
  }
  function setCaretPosition(elem, caretPos) {
    try {
      if (elem) {
        if (elem.createTextRange) {
          var range = elem.createTextRange();
          range.move('character', caretPos);
          range.select();
        } else {
          elem.setSelectionRange(caretPos, caretPos);
        }
      }
    } catch (ex) {// ignored
    }
  }
  function setValues(that) {
    var field = null;
    var result = [];
    var searchControls = getSearchControls(that);

    if (that._valuesFilterControl.length > 0) {
      //  Callback to apply after settings fields values
      var callbacks = [];
      searchControls.each(function (i, el) {
        var $this = $__default["default"](el);
        field = $this.closest('[data-field]').data('field');
        result = that._valuesFilterControl.filter(function (valueObj) {
          return valueObj.field === field;
        });

        if (result.length > 0) {
          if (result[0].hasFocus || result[0].value) {
            var fieldToFocusCallback = function (element, cacheElementInfo) {
              // Closure here to capture the field information
              var closedCallback = function closedCallback() {
                if (cacheElementInfo.hasFocus) {
                  element.focus();
                }

                if (Array.isArray(cacheElementInfo.value)) {
                  var $element = $__default["default"](element);
                  $__default["default"].each(cacheElementInfo.value, function (i, e) {
                    $element.find(Utils.sprintf('option[value=\'%s\']', e)).prop('selected', true);
                  });
                } else {
                  element.value = cacheElementInfo.value;
                }

                setCaretPosition(element, cacheElementInfo.position);
              };

              return closedCallback;
            }($this.get(0), result[0]);

            callbacks.push(fieldToFocusCallback);
          }
        }
      }); // Callback call.

      if (callbacks.length > 0) {
        callbacks.forEach(function (callback) {
          return callback();
        });
      }
    }
  }
  function collectBootstrapTableFilterCookies() {
    var cookies = [];
    var foundCookies = document.cookie.match(/bs\.table\.(filterControl|searchText)/g);
    var foundLocalStorage = localStorage;

    if (foundCookies) {
      $__default["default"].each(foundCookies, function (i, _cookie) {
        var cookie = _cookie;

        if (/./.test(cookie)) {
          cookie = cookie.split('.').pop();
        }

        if ($__default["default"].inArray(cookie, cookies) === -1) {
          cookies.push(cookie);
        }
      });
    }

    if (foundLocalStorage) {
      for (var i = 0; i < foundLocalStorage.length; i++) {
        var cookie = foundLocalStorage.key(i);

        if (/./.test(cookie)) {
          cookie = cookie.split('.').pop();
        }

        if (!cookies.includes(cookie)) {
          cookies.push(cookie);
        }
      }
    }

    return cookies;
  }
  function escapeID(id) {
    // eslint-disable-next-line no-useless-escape
    return String(id).replace(/([:.\[\],])/g, '\\$1');
  }
  function isColumnSearchableViaSelect(_ref2) {
    var filterControl = _ref2.filterControl,
        searchable = _ref2.searchable;
    return filterControl && filterControl.toLowerCase() === 'select' && searchable;
  }
  function isFilterDataNotGiven(_ref3) {
    var filterData = _ref3.filterData;
    return filterData === undefined || filterData.toLowerCase() === 'column';
  }
  function hasSelectControlElement(selectControl) {
    return selectControl && selectControl.length > 0;
  }
  function initFilterSelectControls(that) {
    var data = that.options.data;
    $__default["default"].each(that.header.fields, function (j, field) {
      var column = that.columns[that.fieldsColumnsIndex[field]];
      var selectControl = getControlContainer(that).find("select.bootstrap-table-filter-control-".concat(escapeID(column.field)));

      if (isColumnSearchableViaSelect(column) && isFilterDataNotGiven(column) && hasSelectControlElement(selectControl)) {
        if (!selectControl[0].multiple && selectControl.get(selectControl.length - 1).options.length === 0) {
          // Added the default option, must use a non-breaking space(&nbsp;) to pass the W3C validator
          addOptionToSelectControl(selectControl, '', column.filterControlPlaceholder || ' ', column.filterDefault);
        }

        var uniqueValues = {};

        for (var i = 0; i < data.length; i++) {
          // Added a new value
          var fieldValue = Utils.getItemField(data[i], field, false);
          var formatter = that.options.editable && column.editable ? column._formatter : that.header.formatters[j];
          var formattedValue = Utils.calculateObjectValue(that.header, formatter, [fieldValue, data[i], i], fieldValue);

          if (!fieldValue) {
            fieldValue = formattedValue;
            column._forceFormatter = true;
          }

          if (column.filterDataCollector) {
            formattedValue = Utils.calculateObjectValue(that.header, column.filterDataCollector, [fieldValue, data[i], formattedValue], formattedValue);
          }

          if (column.searchFormatter) {
            fieldValue = formattedValue;
          }

          uniqueValues[formattedValue] = fieldValue;

          if (_typeof(formattedValue) === 'object' && formattedValue !== null) {
            formattedValue.forEach(function (value) {
              addOptionToSelectControl(selectControl, value, value, column.filterDefault);
            });
            continue;
          }
        } // eslint-disable-next-line guard-for-in


        for (var key in uniqueValues) {
          addOptionToSelectControl(selectControl, uniqueValues[key], key, column.filterDefault);
        }

        if (that.options.sortSelectOptions) {
          sortSelectControl(selectControl, 'asc', that.options);
        }
      }
    });
  }
  function getFilterDataMethod(objFilterDataMethod, searchTerm) {
    var keys = Object.keys(objFilterDataMethod);

    for (var i = 0; i < keys.length; i++) {
      if (keys[i] === searchTerm) {
        return objFilterDataMethod[searchTerm];
      }
    }

    return null;
  }
  function createControls(that, header) {
    var addedFilterControl = false;
    var html;
    $__default["default"].each(that.columns, function (_, column) {
      html = [];

      if (!column.visible && !(that.options.filterControlContainer && $__default["default"](".bootstrap-table-filter-control-".concat(column.field)).length >= 1)) {
        return;
      }

      if (!column.filterControl && !that.options.filterControlContainer) {
        html.push('<div class="no-filter-control"></div>');
      } else if (that.options.filterControlContainer) {
        // Use a filter control container instead of th
        var $filterControls = $__default["default"](".bootstrap-table-filter-control-".concat(column.field));
        $__default["default"].each($filterControls, function (_, filterControl) {
          var $filterControl = $__default["default"](filterControl);

          if (!$filterControl.is('[type=radio]')) {
            var placeholder = column.filterControlPlaceholder || '';
            $filterControl.attr('placeholder', placeholder).val(column.filterDefault);
          }

          $filterControl.attr('data-field', column.field);
        });
        addedFilterControl = true;
      } else {
        // Create the control based on the html defined in the filterTemplate array.
        var nameControl = column.filterControl.toLowerCase();
        html.push('<div class="filter-control">');
        addedFilterControl = true;

        if (column.searchable && that.options.filterTemplate[nameControl]) {
          html.push(that.options.filterTemplate[nameControl](that, column, column.filterControlPlaceholder ? column.filterControlPlaceholder : '', column.filterDefault));
        }
      } // Filtering by default when it is set.


      if (column.filterControl && '' !== column.filterDefault && 'undefined' !== typeof column.filterDefault) {
        if ($__default["default"].isEmptyObject(that.filterColumnsPartial)) {
          that.filterColumnsPartial = {};
        }

        if (!(column.field in that.filterColumnsPartial)) {
          that.filterColumnsPartial[column.field] = column.filterDefault;
        }
      }

      $__default["default"].each(header.find('th'), function (_, th) {
        var $th = $__default["default"](th);

        if ($th.data('field') === column.field) {
          $th.find('.filter-control').remove();
          $th.find('.fht-cell').html(html.join(''));
          return false;
        }
      });

      if (column.filterData && column.filterData.toLowerCase() !== 'column') {
        var filterDataType = getFilterDataMethod(filterDataMethods, column.filterData.substring(0, column.filterData.indexOf(':')));
        var filterDataSource;
        var selectControl;

        if (filterDataType) {
          filterDataSource = column.filterData.substring(column.filterData.indexOf(':') + 1, column.filterData.length);
          selectControl = header.find(".bootstrap-table-filter-control-".concat(escapeID(column.field)));
          addOptionToSelectControl(selectControl, '', column.filterControlPlaceholder, column.filterDefault, true);
          filterDataType(that, filterDataSource, selectControl, that.options.filterOrderBy, column.filterDefault);
        } else {
          throw new SyntaxError('Error. You should use any of these allowed filter data methods: var, obj, json, url, func.' + ' Use like this: var: {key: "value"}');
        }
      }
    });

    if (addedFilterControl) {
      header.off('keyup', 'input').on('keyup', 'input', function (_ref4, obj) {
        var currentTarget = _ref4.currentTarget,
            keyCode = _ref4.keyCode;
        keyCode = obj ? obj.keyCode : keyCode;

        if (that.options.searchOnEnterKey && keyCode !== 13) {
          return;
        }

        if (isKeyAllowed(keyCode)) {
          return;
        }

        var $currentTarget = $__default["default"](currentTarget);

        if ($currentTarget.is(':checkbox') || $currentTarget.is(':radio')) {
          return;
        }

        clearTimeout(currentTarget.timeoutId || 0);
        currentTarget.timeoutId = setTimeout(function () {
          that.onColumnSearch({
            currentTarget: currentTarget,
            keyCode: keyCode
          });
        }, that.options.searchTimeOut);
      });
      header.off('change', 'select', '.fc-multipleselect').on('change', 'select', '.fc-multipleselect', function (_ref5) {
        var currentTarget = _ref5.currentTarget,
            keyCode = _ref5.keyCode;
        var $selectControl = $__default["default"](currentTarget);
        var value = $selectControl.val();

        if (Array.isArray(value)) {
          for (var i = 0; i < value.length; i++) {
            if (value[i] && value[i].length > 0 && value[i].trim()) {
              $selectControl.find("option[value=\"".concat(value[i], "\"]")).attr('selected', true);
            }
          }
        } else if (value && value.length > 0 && value.trim()) {
          $selectControl.find('option[selected]').removeAttr('selected');
          $selectControl.find("option[value=\"".concat(value, "\"]")).attr('selected', true);
        } else {
          $selectControl.find('option[selected]').removeAttr('selected');
        }

        clearTimeout(currentTarget.timeoutId || 0);
        currentTarget.timeoutId = setTimeout(function () {
          that.onColumnSearch({
            currentTarget: currentTarget,
            keyCode: keyCode
          });
        }, that.options.searchTimeOut);
      });
      header.off('mouseup', 'input:not([type=radio])').on('mouseup', 'input:not([type=radio])', function (_ref6) {
        var currentTarget = _ref6.currentTarget,
            keyCode = _ref6.keyCode;
        var $input = $__default["default"](currentTarget);
        var oldValue = $input.val();

        if (oldValue === '') {
          return;
        }

        setTimeout(function () {
          var newValue = $input.val();

          if (newValue === '') {
            clearTimeout(currentTarget.timeoutId || 0);
            currentTarget.timeoutId = setTimeout(function () {
              that.onColumnSearch({
                currentTarget: currentTarget,
                keyCode: keyCode
              });
            }, that.options.searchTimeOut);
          }
        }, 1);
      });
      header.off('change', 'input[type=radio]').on('change', 'input[type=radio]', function (_ref7) {
        var currentTarget = _ref7.currentTarget,
            keyCode = _ref7.keyCode;
        clearTimeout(currentTarget.timeoutId || 0);
        currentTarget.timeoutId = setTimeout(function () {
          that.onColumnSearch({
            currentTarget: currentTarget,
            keyCode: keyCode
          });
        }, that.options.searchTimeOut);
      }); // See https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date

      if (header.find('.date-filter-control').length > 0) {
        $__default["default"].each(that.columns, function (i, _ref8) {
          var filterDefault = _ref8.filterDefault,
              filterControl = _ref8.filterControl,
              field = _ref8.field,
              filterDatepickerOptions = _ref8.filterDatepickerOptions;

          if (filterControl !== undefined && filterControl.toLowerCase() === 'datepicker') {
            var $datepicker = header.find(".date-filter-control.bootstrap-table-filter-control-".concat(field));

            if (filterDefault) {
              $datepicker.value(filterDefault);
            }

            if (filterDatepickerOptions.min) {
              $datepicker.attr('min', filterDatepickerOptions.min);
            }

            if (filterDatepickerOptions.max) {
              $datepicker.attr('max', filterDatepickerOptions.max);
            }

            if (filterDatepickerOptions.step) {
              $datepicker.attr('step', filterDatepickerOptions.step);
            }

            if (filterDatepickerOptions.pattern) {
              $datepicker.attr('pattern', filterDatepickerOptions.pattern);
            }

            $datepicker.on('change', function (_ref9) {
              var currentTarget = _ref9.currentTarget;
              clearTimeout(currentTarget.timeoutId || 0);
              currentTarget.timeoutId = setTimeout(function () {
                that.onColumnSearch({
                  currentTarget: currentTarget
                });
              }, that.options.searchTimeOut);
            });
          }
        });
      }

      if (that.options.sidePagination !== 'server') {
        that.triggerSearch();
      }

      if (!that.options.filterControlVisible) {
        header.find('.filter-control, .no-filter-control').hide();
      }
    } else {
      header.find('.filter-control, .no-filter-control').hide();
    }

    that.trigger('created-controls');
  }
  function getDirectionOfSelectOptions(_alignment) {
    var alignment = _alignment === undefined ? 'left' : _alignment.toLowerCase();

    switch (alignment) {
      case 'left':
        return 'ltr';

      case 'right':
        return 'rtl';

      case 'auto':
        return 'auto';

      default:
        return 'ltr';
    }
  }
  function syncHeaders(that) {
    if (!that.options.height) {
      return;
    }

    var fixedHeader = $__default["default"]('.fixed-table-header table thead');

    if (fixedHeader.length === 0) {
      return;
    }

    that.$header.children().find('th[data-field]').each(function (_, element) {
      if (element.classList[0] !== 'bs-checkbox') {
        var $element = $__default["default"](element);
        var $field = $element.data('field');
        var $fixedField = $__default["default"]("th[data-field='".concat($field, "']")).not($element);
        var input = $element.find('input');
        var fixedInput = $fixedField.find('input');

        if (input.length > 0 && fixedInput.length > 0) {
          if (input.val() !== fixedInput.val()) {
            input.val(fixedInput.val());
          }
        }
      }
    });
  }
  var filterDataMethods = {
    func: function func(that, filterDataSource, selectControl, filterOrderBy, selected) {
      var variableValues = window[filterDataSource].apply(); // eslint-disable-next-line guard-for-in

      for (var key in variableValues) {
        addOptionToSelectControl(selectControl, key, variableValues[key], selected);
      }

      if (that.options.sortSelectOptions) {
        sortSelectControl(selectControl, filterOrderBy, that.options);
      }

      setValues(that);
    },
    obj: function obj(that, filterDataSource, selectControl, filterOrderBy, selected) {
      var objectKeys = filterDataSource.split('.');
      var variableName = objectKeys.shift();
      var variableValues = window[variableName];

      if (objectKeys.length > 0) {
        objectKeys.forEach(function (key) {
          variableValues = variableValues[key];
        });
      } // eslint-disable-next-line guard-for-in


      for (var key in variableValues) {
        addOptionToSelectControl(selectControl, key, variableValues[key], selected);
      }

      if (that.options.sortSelectOptions) {
        sortSelectControl(selectControl, filterOrderBy, that.options);
      }

      setValues(that);
    },
    var: function _var(that, filterDataSource, selectControl, filterOrderBy, selected) {
      var variableValues = window[filterDataSource];
      var isArray = Array.isArray(variableValues);

      for (var key in variableValues) {
        if (isArray) {
          addOptionToSelectControl(selectControl, variableValues[key], variableValues[key], selected, true);
        } else {
          addOptionToSelectControl(selectControl, key, variableValues[key], selected, true);
        }
      }

      if (that.options.sortSelectOptions) {
        sortSelectControl(selectControl, filterOrderBy, that.options);
      }

      setValues(that);
    },
    url: function url(that, filterDataSource, selectControl, filterOrderBy, selected) {
      $__default["default"].ajax({
        url: filterDataSource,
        dataType: 'json',
        success: function success(data) {
          // eslint-disable-next-line guard-for-in
          for (var key in data) {
            addOptionToSelectControl(selectControl, key, data[key], selected);
          }

          if (that.options.sortSelectOptions) {
            sortSelectControl(selectControl, filterOrderBy, that.options);
          }

          setValues(that);
        }
      });
    },
    json: function json(that, filterDataSource, selectControl, filterOrderBy, selected) {
      var variableValues = JSON.parse(filterDataSource); // eslint-disable-next-line guard-for-in

      for (var key in variableValues) {
        addOptionToSelectControl(selectControl, key, variableValues[key], selected);
      }

      if (that.options.sortSelectOptions) {
        sortSelectControl(selectControl, filterOrderBy, that.options);
      }

      setValues(that);
    }
  };

  exports.addOptionToSelectControl = addOptionToSelectControl;
  exports.cacheValues = cacheValues;
  exports.collectBootstrapTableFilterCookies = collectBootstrapTableFilterCookies;
  exports.createControls = createControls;
  exports.escapeID = escapeID;
  exports.existOptionInSelectControl = existOptionInSelectControl;
  exports.fixHeaderCSS = fixHeaderCSS;
  exports.getControlContainer = getControlContainer;
  exports.getCursorPosition = getCursorPosition;
  exports.getDirectionOfSelectOptions = getDirectionOfSelectOptions;
  exports.getElementClass = getElementClass;
  exports.getFilterDataMethod = getFilterDataMethod;
  exports.getInputClass = getInputClass;
  exports.getOptionsFromSelectControl = getOptionsFromSelectControl;
  exports.getSearchControls = getSearchControls;
  exports.hasSelectControlElement = hasSelectControlElement;
  exports.hideUnusedSelectOptions = hideUnusedSelectOptions;
  exports.initFilterSelectControls = initFilterSelectControls;
  exports.isColumnSearchableViaSelect = isColumnSearchableViaSelect;
  exports.isFilterDataNotGiven = isFilterDataNotGiven;
  exports.isKeyAllowed = isKeyAllowed;
  exports.setCaretPosition = setCaretPosition;
  exports.setValues = setValues;
  exports.sortSelectControl = sortSelectControl;
  exports.syncHeaders = syncHeaders;

  Object.defineProperty(exports, '__esModule', { value: true });

}));
