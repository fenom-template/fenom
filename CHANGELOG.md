Changelog
=========

### 1.4.1 (2013-09-05)

- Fix equating for {case} in {switch}
- Fix ternary operator when option `force_verify` is enabled
- Docs++

## 1.4.0 (2013-09-02)

- Redesign tag {switch}
- Add tag {insert}
- Add variable verification before using (option `Fenom::FORCE_VERIFY`)
- Improve internal parsers
- Fix #45: intersection of names of tmp vars
- Fix #44: invalid `_depend` format in template
- Docs++
- Tests++

### 1.3.1 (2013-08-29)

- Fix: accessor don't work in modifier
- Removed too many EOLs in template code
- Tests++

## 1.3.0 (2013-08-23)

- Feature #41: Add system variable `$`.
- Fix bug when recursive macros doesn't work in `Fenom\Template`
- Recognize variable parser
- Recognize macros parser
- Fix `auto_reload` option
- Tests++
- Docs++

### 1.2.2 (2013-08-07)

- Fix bug in setOptions method

### 1.2.1 (2013-08-06)

- Fix #39: compile error with boolean operators

## 1.2.0 (2013-08-05)

- Feature #28: macros may be called recursively
- Feature #29: add {unset} tag
- Add hook for loading modifiers and tags
- Feature #3: Add string operator '~'
- Improve parsers: parserExp, parserVar, parserVariable, parserMacro
- Fix ternary bug
- Bugs--
- Tests++
- Docs++

### 1.1.1 (2013-07-24)

- Bug fixes

## 1.1.0 (2013-07-22)

- Bug #19: Bug with "if" expressions starting with "("
- Bug #16: Allow modifiers for function calls
- Bug #25: Invalid option flag for `auto_reload`
- Bug: Invalid options for cached templates
- Bug: Removed memory leak after render
- Fix nested bracket pull #10
- Fix bugs with provider
- Improve providers' performance
- Improve #1: Add `is` and `in` operator
- Remove Fenom::addTemplate(). Use providers for adding custom templates.
- Big refractory: parsers, providers, storage
- Improve tokenizer
- Internal optimization
- Add options for benchmark
- Add stress test (thanks to @klkvsk)
- Bugs--
- Comments++
- Docs++
- Test++

### 1.0.8 (2013-07-07)

- Perform auto_escape options
- Fix bugs
- Update documentation

### 1.0.7 (2013-07-07)

- Perform auto_escape options
- Fix bugs

### 1.0.6 (2013-07-04)

- Fix modifiers insertions

### 1.0.5 (2013-07-04)

- Add `Fenom::AUTO_ESCAPE` support (feature #2)
- Update documentation

### 1.0.4 (2013-06-27)

- Add nested level for {extends} and {use}
- Small bug fix
- Update documentation

### 1.0.3 (2013-06-20)

- Allow any callable for modifier (instead string)
- Bug fix
- Update documentation

### 1.0.2 (2013-06-18)

- Optimize extends
- Bug fix
- Update documentation

### 1.0.1 (2013-05-30)

- Bug fix
- comments don't work

## 1.0.0 (2013-05-30)

- First release
