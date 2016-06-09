Changelog
=========

## 2.11.0 (2016-06-09)

- Added method to get the name of the cache template `$fenom->getCacheName($template_name)`(#231)
- Fix bug with before-code in template inheritance (#229)
- Added `??` operator.
- Improve compile mechanism
- ++Docs
- ++Test

## 2.10.0 (2016-05-08)

- Add tag `{do ...}`
- ++Docs
- ++Tests

## 2.9.0 (2016-05-08)

- Add `$.block`
- Refactory range
- Refactory blocks
- Docs

...

## 2.6.0 (2015-02-22)

- Add range operator (`1..3`)
- Tag `for` now is deprecated, use tag `foreach` with range
- Internal improves

### 2.5.4 (2015-02-19)

- Fix bug #152
- Add composer.lock to git

### 2.5.3 (2015-02-19)

- Fix bug #147

### 2.5.2 (2015-02-10)

- Fix bug: unexpected array conversion when object given to {foreach} with force verify option (pull #148)

### 2.5.1 (2015-02-10)

- Fix bugs #144, #135


## 2.5.0 (2015-02-01)

- Internal improvement: functions accept array of template variables 
- Improve `in` operator
- Fix bug #142

### 2.4.6 (2015-01-30)

- Fix bug #138

### 2.4.5 (2015-01-30)

Move project to organization `fenom-template`

### 2.4.4 (2015-01-22)

- Fix: parse error then modifier's argument converts to false

### 2.4.3 (2015-01-08)

- Fix #132

### 2.4.2 (2015-01-07)

- Internal improvements and code cleaning

### 2.4.2 (2015-01-07)

- Fix bug #128

## 2.4.0 (2015-01-02)

- Fix bugs #120, #104, #119
- Add `~~` operator. Concatenation with space. 
- Improve #126. Disable clearcachestats() by default in Fenom\Provider. clearcachestats() may be enabled.
- Improve accessors (unnamed system variable). Now possible add, redefine yours accessors.
- ++Docs
- ++Tests

### 2.3.1 (2014-11-06)

- Fix #122

### 2.3.1 (2014-08-27)

- Fix #105
- ++Tests

## 2.3.0 (2014-08-08)

- Add tags {set} and {add}
- Fix bug #97
- ++Docs
- --Bugs
- ++Tests

### 2.2.1 (2014-07-29)

- ++Docs
- --Bugs

## 2.2.0 (2014-07-11)
- Add new modifiers: match, ematch, replace, ereplace, split, esplit, join
- ++Docs
- ++Tests

### 2.1.2 (2014-07-03)

- Add test for bug #86 
- Fix bug #90 
- --Bugs
- ++Tests

### 2.1.1 (2014-06-30)

- Fix bug #86: mismatch semicolon separator when value for foreach got from method  (by nekufa)

## 2.1.0 (2014-06-29)

- Check variable before using in {foreach} (#83)
- Add tag {unset} (#80)
- Refactory array parser
- --Bugs
- ++Tests
- ++Docs

### 2.0.1 (2014-06-09)

- Fix string concatenation. If `~` in the end of expression Fenom generates broken template.
- Fix `~=` operator. Operator was not working.
- ++Tests
- ++Docs

## 2.0.0

- Add tag the {filter}
- Redesign `extends` algorithm:
    - Blocks don't support dynamic names
    - Blocks can't be nested
- Add tag options support
- Improve Fenom API
- Move benchmark to another project
- Internal improvements
- Add `Fenom::STRIP` option
- Add tags {escape} and {strip}
- Method addProvider accept compile path which will saved the template's PHP cache. If compile path is not specified, will be taken global compile path.

### 1.4.9 (2013-04-09)

- Fix #75
- Docs++

### 1.4.8 (2013-12-01)

- Fix #52
- Tests++

### 1.4.7 (2013-09-21)

- Bug fixes
- Tests++

### 1.4.6 (2013-09-19)

- Bug fixes
- Tests++

### 1.4.5 (2013-09-15)

- Bug fixes
- Tests++

### 1.4.4 (2013-09-13)

- Bug fixes
- Tests++

### 1.4.3 (2013-09-10)

- Bug fixes

### 1.4.2 (2013-09-06)

- Added check the cache directory to record

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
