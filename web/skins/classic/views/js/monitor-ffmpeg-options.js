//
// ZoneMinder Monitor FFmpeg Options
//
// UMD-pattern module: exposed as window.ZM_FfmpegOptions in browsers and via
// module.exports in Node for unit testing.
//
// zmc hands a monitor's Options field to av_dict_parse_string() with "=" between
// key and value and kOptionSeparators (src/zm_utils.h) between pairs. When that
// fails zmc ignores the rest of the field, so the form checks it before saving.
//
(function(global) {
  'use strict';

  const WHITESPACES = ' \n\t\r';
  const PAIR_SEPARATORS = ',\r\n';

  // Port of av_get_token() from libavutil/avstring.c: skips leading whitespace,
  // honours \ escapes and '' quoting, and trims trailing whitespace that was not
  // escaped or quoted.
  function getToken(text, pos, term) {
    while (pos < text.length && WHITESPACES.includes(text[pos])) pos++;
    let out = '';
    let end = 0;
    while (pos < text.length && !term.includes(text[pos])) {
      const c = text[pos++];
      if (c == '\\' && pos < text.length) {
        out += text[pos++];
        end = out.length;
      } else if (c == '\'') {
        while (pos < text.length && text[pos] != '\'') out += text[pos++];
        if (pos < text.length) {
          pos++;
          end = out.length;
        }
      } else {
        out += c;
      }
    }
    while (out.length > end && WHITESPACES.includes(out[out.length - 1])) {
      out = out.slice(0, -1);
    }
    return {token: out, pos: pos};
  }

  // Port of av_dict_parse_string() from libavutil/dict.c. Returns the parsed
  // pairs, and on failure the text of the entry ffmpeg stopped at.
  function parse(text, separators) {
    const seps = separators || PAIR_SEPARATORS;
    const pairs = [];
    let pos = 0;
    text = text ? String(text) : '';
    while (pos < text.length) {
      const start = pos;
      const key = getToken(text, pos, '=');
      pos = key.pos;
      let value = null;
      if (key.token && text[pos] == '=') {
        value = getToken(text, pos + 1, seps);
        pos = value.pos;
      }
      if (!key.token || !value || !value.token) {
        let stop = start;
        while (stop < text.length && !seps.includes(text[stop])) stop++;
        return {pairs: pairs, error: text.slice(start, stop).trim() || text.slice(start)};
      }
      pairs.push({key: key.token, value: value.token});
      if (pos < text.length) pos++;
    }
    return {pairs: pairs, error: null};
  }

  // Entries that zmc cannot use: one that ffmpeg fails to parse, and keys that
  // parse but can name no option. A key runs up to the next =, so
  // "a=1,tls_verify 0,b=2" gives the key "tls_verify 0,b", and command line
  // flags give "-tls_verify". Returns the offending entries, empty when all is
  // well.
  function badEntries(text, separators) {
    const seps = separators || PAIR_SEPARATORS;
    const result = parse(text, seps);
    const bad = [];
    for (const pair of result.pairs) {
      if (pair.key[0] == '-' || [...WHITESPACES + seps].some((c) => pair.key.includes(c))) {
        bad.push(pair.key + '=' + pair.value);
      }
    }
    if (result.error !== null) bad.push(result.error);
    return bad;
  }

  const api = {
    parse: parse,
    badEntries: badEntries,
  };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
  } else {
    global.ZM_FfmpegOptions = api;
  }
})(typeof window !== 'undefined' ? window : globalThis);
