(function () {
    'use strict';

    var cashaddr = {};

    var base32 = {};

    var validation = {};

    var hasRequiredValidation;

    function requireValidation () {
    	if (hasRequiredValidation) return validation;
    	hasRequiredValidation = 1;
    	/**
    	 * @license
    	 * https://reviews.bitcoinabc.org
    	 * Copyright (c) 2017-2020 Emilio Almansi
    	 * Copyright (c) 2023 Bitcoin ABC
    	 * Distributed under the MIT software license, see the accompanying
    	 * file LICENSE or http://www.opensource.org/licenses/mit-license.php.
    	 */
    	Object.defineProperty(validation, "__esModule", { value: true });
    	/**
    	 * Validation utility.
    	 *
    	 * @module validation
    	 */
    	/**
    	 * Error thrown when encoding or decoding fail due to invalid input.
    	 *
    	 * @constructor ValidationError
    	 * @param {string} message Error description.
    	 */
    	class ValidationError extends Error {
    	    constructor(message) {
    	        super(message); // Call the parent constructor
    	        this.name = 'ValidationError'; // Set the error name
    	        // If targeting ES5 or earlier, need to set this manually for subclassing to work
    	        Object.setPrototypeOf(this, ValidationError.prototype);
    	    }
    	}
    	/**
    	 * Validates a given condition, throwing a {@link ValidationError} if
    	 * the given condition does not hold.
    	 *
    	 * @static
    	 * @param condition Condition to validate.
    	 * @param message Error message in case the condition does not hold.
    	 */
    	function validate(condition, message) {
    	    if (!condition) {
    	        throw new ValidationError(message);
    	    }
    	}
    	validation.default = {
    	    ValidationError: ValidationError,
    	    validate: validate,
    	};
    	return validation;
    }

    var hasRequiredBase32;

    function requireBase32 () {
    	if (hasRequiredBase32) return base32;
    	hasRequiredBase32 = 1;
    	(function (exports) {
    		/**
    		 * @license
    		 * https://reviews.bitcoinabc.org
    		 * Copyright (c) 2017-2020 Emilio Almansi
    		 * Copyright (c) 2023-2024 Bitcoin ABC
    		 * Distributed under the MIT software license, see the accompanying
    		 * file LICENSE or http://www.opensource.org/licenses/mit-license.php.
    		 */
    		var __importDefault = (base32 && base32.__importDefault) || function (mod) {
    		    return (mod && mod.__esModule) ? mod : { "default": mod };
    		};
    		Object.defineProperty(exports, "__esModule", { value: true });
    		exports.CHARSET = void 0;
    		const validation_1 = __importDefault(requireValidation());
    		const { validate } = validation_1.default;
    		/**
    		 * Base32 encoding and decoding.
    		 *
    		 * @module base32
    		 */
    		/**
    		 * Charset containing the 32 symbols used in the base32 encoding.
    		 * @private
    		 */
    		exports.CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
    		/**
    		 * Inverted index mapping each symbol into its index within the charset.
    		 * @private
    		 */
    		const CHARSET_INVERSE_INDEX = {
    		    q: 0,
    		    p: 1,
    		    z: 2,
    		    r: 3,
    		    y: 4,
    		    9: 5,
    		    x: 6,
    		    8: 7,
    		    g: 8,
    		    f: 9,
    		    2: 10,
    		    t: 11,
    		    v: 12,
    		    d: 13,
    		    w: 14,
    		    0: 15,
    		    s: 16,
    		    3: 17,
    		    j: 18,
    		    n: 19,
    		    5: 20,
    		    4: 21,
    		    k: 22,
    		    h: 23,
    		    c: 24,
    		    e: 25,
    		    6: 26,
    		    m: 27,
    		    u: 28,
    		    a: 29,
    		    7: 30,
    		    l: 31,
    		};
    		/**
    		 * Encodes the given array of 5-bit integers as a base32-encoded string.
    		 *
    		 * @static
    		 * @param data Array of integers between 0 and 31 inclusive.
    		 * @throws {ValidationError}
    		 */
    		function encode(data) {
    		    validate(data instanceof Uint8Array, 'Invalid data: ' + data + '.');
    		    let base32 = '';
    		    for (let i = 0; i < data.length; ++i) {
    		        const value = data[i];
    		        validate(0 <= value && value < 32, 'Invalid value: ' + value + '.');
    		        base32 += exports.CHARSET[value];
    		    }
    		    return base32;
    		}
    		/**
    		 * Decodes the given base32-encoded string into an array of 5-bit integers.
    		 *
    		 * @static
    		 * @param string
    		 * @throws {ValidationError}
    		 */
    		function decode(string) {
    		    validate(typeof string === 'string', 'Invalid base32-encoded string: ' + string + '.');
    		    const data = new Uint8Array(string.length);
    		    for (let i = 0; i < string.length; ++i) {
    		        const value = string[i];
    		        validate(value in CHARSET_INVERSE_INDEX, 'Invalid value: ' + value + '.');
    		        data[i] = CHARSET_INVERSE_INDEX[value];
    		    }
    		    return data;
    		}
    		exports.default = {
    		    encode: encode,
    		    decode: decode,
    		}; 
    	} (base32));
    	return base32;
    }

    var convertBits = {};

    var hasRequiredConvertBits;

    function requireConvertBits () {
    	if (hasRequiredConvertBits) return convertBits;
    	hasRequiredConvertBits = 1;
    	// Copyright (c) 2017-2018 Emilio Almansi
    	// Copyright (c) 2017 Pieter Wuille
    	// Copyright (c) 2024 Bitcoin ABC
    	//
    	// Permission is hereby granted, free of charge, to any person obtaining a copy
    	// of this software and associated documentation files (the "Software"), to deal
    	// in the Software without restriction, including without limitation the rights
    	// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    	// copies of the Software, and to permit persons to whom the Software is
    	// furnished to do so, subject to the following conditions:
    	//
    	// The above copyright notice and this permission notice shall be included in
    	// all copies or substantial portions of the Software.
    	//
    	// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    	// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    	// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    	// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    	// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    	// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    	// THE SOFTWARE.
    	var __importDefault = (convertBits && convertBits.__importDefault) || function (mod) {
    	    return (mod && mod.__esModule) ? mod : { "default": mod };
    	};
    	Object.defineProperty(convertBits, "__esModule", { value: true });
    	convertBits.default = default_1;
    	const validation_1 = __importDefault(requireValidation());
    	const { validate } = validation_1.default;
    	/**
    	 * Converts an array of integers made up of 'from' bits into an
    	 * array of integers made up of 'to' bits. The output array is
    	 * zero-padded if necessary, unless strict mode is true.
    	 * Throws a {@link ValidationError} if input is invalid.
    	 * Original by Pieter Wuille: https://github.com/sipa/bech32.
    	 *
    	 * @param data Array of integers made up of 'from' bits.
    	 * @param from Length in bits of elements in the input array.
    	 * @param to Length in bits of elements in the output array.
    	 * @param strictMode Require the conversion to be completed without padding.
    	 */
    	function default_1(data, from, to, strictMode = false) {
    	    const length = strictMode
    	        ? Math.floor((data.length * from) / to)
    	        : Math.ceil((data.length * from) / to);
    	    const mask = (1 << to) - 1;
    	    const result = new Uint8Array(length);
    	    let index = 0;
    	    let accumulator = 0;
    	    let bits = 0;
    	    for (let i = 0; i < data.length; ++i) {
    	        const value = data[i];
    	        validate(0 <= value && value >> from === 0, 'Invalid value: ' + value + '.');
    	        accumulator = (accumulator << from) | value;
    	        bits += from;
    	        while (bits >= to) {
    	            bits -= to;
    	            result[index] = (accumulator >> bits) & mask;
    	            ++index;
    	        }
    	    }
    	    if (!strictMode) {
    	        if (bits > 0) {
    	            result[index] = (accumulator << (to - bits)) & mask;
    	            ++index;
    	        }
    	    }
    	    else {
    	        validate(bits < from && ((accumulator << (to - bits)) & mask) === 0, 'Input cannot be converted to ' +
    	            to +
    	            ' bits without padding, but strict mode was used.');
    	    }
    	    return result;
    	}
    	return convertBits;
    }

    var hasRequiredCashaddr;

    function requireCashaddr () {
    	if (hasRequiredCashaddr) return cashaddr;
    	hasRequiredCashaddr = 1;
    	(function (exports) {
    		/**
    		 * @license
    		 * https://reviews.bitcoinabc.org
    		 * Copyright (c) 2017-2020 Emilio Almansi
    		 * Copyright (c) 2023-2024 Bitcoin ABC
    		 * Distributed under the MIT software license, see the accompanying
    		 * file LICENSE or http://www.opensource.org/licenses/mit-license.php.
    		 */
    		var __importDefault = (cashaddr && cashaddr.__importDefault) || function (mod) {
    		    return (mod && mod.__esModule) ? mod : { "default": mod };
    		};
    		Object.defineProperty(exports, "__esModule", { value: true });
    		exports.getOutputScriptFromTypeAndHash = exports.VALID_PREFIXES = void 0;
    		exports.encodeCashAddress = encodeCashAddress;
    		exports.decodeCashAddress = decodeCashAddress;
    		exports.uint8arrayToHexString = uint8arrayToHexString;
    		exports.getTypeAndHashFromOutputScript = getTypeAndHashFromOutputScript;
    		exports.encodeOutputScript = encodeOutputScript;
    		exports.isValidCashAddress = isValidCashAddress;
    		exports.getOutputScriptFromAddress = getOutputScriptFromAddress;
    		const base32_1 = __importDefault(requireBase32());
    		const convertBits_1 = __importDefault(requireConvertBits());
    		const validation_1 = __importDefault(requireValidation());
    		const { validate, ValidationError } = validation_1.default;
    		/**
    		 * Encoding and decoding of the new Cash Address format for eCash. <br />
    		 * Compliant with the original cashaddr specification:
    		 * {@link https://github.com/bitcoincashorg/bitcoincash.org/blob/master/spec/cashaddr.md}
    		 * @module cashaddr
    		 */
    		/**
    		 * Encodes a hash from a given type into an eCash address with the given prefix.
    		 *
    		 * @param prefix Cash address prefix. E.g.: 'ecash'.
    		 * @param type Type of address to generate
    		 * @param hash Hash to encode represented as an array of 8-bit integers.
    		 * @throws {ValidationError}
    		 */
    		function encodeCashAddress(prefix, type, hash) {
    		    validate(typeof prefix === 'string' && isValidPrefix(prefix), 'Invalid prefix: ' + prefix + '.');
    		    validate(type === 'p2pkh' || type === 'p2sh', 'Invalid type: ' + type + '.');
    		    validate(hash instanceof Uint8Array || typeof hash === 'string', 'Invalid hash: ' + hash + '. Must be string or Uint8Array.');
    		    if (typeof hash === 'string') {
    		        hash = stringToUint8Array(hash);
    		    }
    		    const prefixData = concat(prefixToUint5Array(prefix), new Uint8Array(1));
    		    const versionByte = getTypeBits(type) + getHashSizeBits(hash);
    		    const payloadData = toUint5Array(concat(new Uint8Array([versionByte]), hash));
    		    const checksumData = concat(concat(prefixData, payloadData), new Uint8Array(8));
    		    const payload = concat(payloadData, checksumToUint5Array(polymod(checksumData)));
    		    return prefix + ':' + base32_1.default.encode(payload);
    		}
    		/**
    		 * Decodes the given address into its constituting prefix, type and hash. See [#encode()]{@link encode}.
    		 *
    		 * @param address Address to decode. E.g.: 'ecash:qpm2qsznhks23z7629mms6s4cwef74vcwva87rkuu2'.
    		 * @throws {ValidationError}
    		 */
    		function decodeCashAddress(address) {
    		    validate(typeof address === 'string' && hasSingleCase(address), 'Invalid address: ' + address + '.');
    		    const pieces = address.toLowerCase().split(':');
    		    // if there is no prefix, it might still be valid
    		    let prefix, payload;
    		    if (pieces.length === 1) {
    		        // Check and see if it has a valid checksum for accepted prefixes
    		        let hasValidChecksum = false;
    		        for (let i = 0; i < exports.VALID_PREFIXES.length; i += 1) {
    		            const testedPrefix = exports.VALID_PREFIXES[i];
    		            const prefixlessPayload = base32_1.default.decode(pieces[0]);
    		            hasValidChecksum = validChecksum(testedPrefix, prefixlessPayload);
    		            if (hasValidChecksum) {
    		                // Here's your prefix
    		                prefix = testedPrefix;
    		                payload = prefixlessPayload;
    		                // Stop testing other prefixes
    		                break;
    		            }
    		        }
    		        validate(hasValidChecksum, `Prefixless address ${address} does not have valid checksum for any valid prefix (${exports.VALID_PREFIXES.join(', ')})`);
    		    }
    		    else {
    		        validate(pieces.length === 2, 'Invalid address: ' + address + '.');
    		        prefix = pieces[0];
    		        payload = base32_1.default.decode(pieces[1]);
    		        validate(validChecksum(prefix, payload), 'Invalid checksum: ' + address + '.');
    		    }
    		    // We assert that payload will be defined here, as we validate above
    		    const payloadData = fromUint5Array(payload.subarray(0, -8));
    		    const versionByte = payloadData[0];
    		    const hash = payloadData.subarray(1);
    		    validate(getHashSize(versionByte) === hash.length * 8, 'Invalid hash size: ' + address + '.');
    		    const type = getType(versionByte);
    		    return {
    		        prefix: prefix,
    		        type,
    		        hash: uint8arrayToHexString(hash),
    		    };
    		}
    		/**
    		 * All valid address prefixes
    		 * Note that as of 2.0.0 we do not validate against these prefixes
    		 * However we do use them to guess prefix for prefixless addrs
    		 *
    		 * @private
    		 */
    		exports.VALID_PREFIXES = [
    		    'ecash',
    		    'bitcoincash',
    		    'simpleledger',
    		    'etoken',
    		    'ectest',
    		    'ecregtest',
    		    'bchtest',
    		    'bchreg',
    		];
    		/**
    		 * Checks whether a string is a valid prefix
    		 * ie., it has a single letter case and no spaces
    		 * Could be extended to validate for accepted prefixes
    		 *
    		 * @private
    		 * @param prefix
    		 */
    		function isValidPrefix(prefix) {
    		    return hasSingleCase(prefix) && !prefix.includes(' ');
    		}
    		/**
    		 * Derives an array from the given prefix to be used in the computation
    		 * of the address' checksum.
    		 *
    		 * @private
    		 * @param prefix Cash address prefix. E.g.: 'ecash'.
    		 */
    		function prefixToUint5Array(prefix) {
    		    const result = new Uint8Array(prefix.length);
    		    for (let i = 0; i < prefix.length; ++i) {
    		        result[i] = prefix[i].charCodeAt(0) & 31;
    		    }
    		    return result;
    		}
    		/**
    		 * Returns an array representation of the given checksum to be encoded
    		 * within the address' payload.
    		 *
    		 * @private
    		 * @param checksum Computed checksum.
    		 * TODO update big-integer so we can use correct types
    		 */
    		function checksumToUint5Array(checksum) {
    		    const result = new Uint8Array(8);
    		    for (let i = 0; i < 8; ++i) {
    		        // Extract the least significant 5 bits (31 is 11111 in binary)
    		        result[7 - i] = Number(checksum & 31n);
    		        // Shift right by 5 bits
    		        checksum >>= 5n;
    		    }
    		    return result;
    		}
    		/**
    		 * Returns the bit representation of the given type within the version
    		 * byte.
    		 *
    		 * @private
    		 * @param type Address type. Either 'P2PKH' or 'P2SH'.
    		 * @throws {ValidationError}
    		 */
    		function getTypeBits(type) {
    		    switch (type) {
    		        case 'p2pkh':
    		            return 0;
    		        case 'p2sh':
    		            return 8;
    		        default:
    		            throw new ValidationError('Invalid type: ' + type + '.');
    		    }
    		}
    		/**
    		 * Retrieves the address type from its bit representation within the
    		 * version byte.
    		 *
    		 * @private
    		 * @param versionByte
    		 */
    		function getType(versionByte) {
    		    switch (versionByte & 120) {
    		        case 0:
    		            return 'p2pkh';
    		        case 8:
    		            return 'p2sh';
    		        default:
    		            throw new ValidationError('Invalid address type in version byte: ' + versionByte + '.');
    		    }
    		}
    		/**
    		 * Returns the bit representation of the length in bits of the given
    		 * hash within the version byte.
    		 *
    		 * @private
    		 * @param hash Hash to encode represented as an array of 8-bit integers.
    		 * @throws {ValidationError}
    		 */
    		function getHashSizeBits(hash) {
    		    switch (hash.length * 8) {
    		        case 160:
    		            return 0;
    		        case 192:
    		            return 1;
    		        case 224:
    		            return 2;
    		        case 256:
    		            return 3;
    		        case 320:
    		            return 4;
    		        case 384:
    		            return 5;
    		        case 448:
    		            return 6;
    		        case 512:
    		            return 7;
    		        default:
    		            throw new ValidationError('Invalid hash size: ' + hash.length + '.');
    		    }
    		}
    		/**
    		 * Retrieves the the length in bits of the encoded hash from its bit
    		 * representation within the version byte.
    		 *
    		 * @private
    		 * @param versionByte
    		 */
    		function getHashSize(versionByte) {
    		    switch (versionByte & 7) {
    		        case 0:
    		            return 160;
    		        case 1:
    		            return 192;
    		        case 2:
    		            return 224;
    		        case 3:
    		            return 256;
    		        case 4:
    		            return 320;
    		        case 5:
    		            return 384;
    		        case 6:
    		            return 448;
    		        case 7:
    		            return 512;
    		        default:
    		            throw new Error('Invalid input');
    		    }
    		}
    		/**
    		 * Converts an array of 8-bit integers into an array of 5-bit integers,
    		 * right-padding with zeroes if necessary.
    		 *
    		 * @private
    		 * @param {Uint8Array} data
    		 */
    		function toUint5Array(data) {
    		    return (0, convertBits_1.default)(data, 8, 5);
    		}
    		/**
    		 * Converts an array of 5-bit integers back into an array of 8-bit integers,
    		 * removing extra zeroes left from padding if necessary.
    		 * Throws a {@link ValidationError} if input is not a zero-padded array of 8-bit integers.
    		 *
    		 * @private
    		 * @param data
    		 * @throws {ValidationError}
    		 */
    		function fromUint5Array(data) {
    		    return (0, convertBits_1.default)(data, 5, 8, true);
    		}
    		/**
    		 * Returns the concatenation a and b.
    		 *
    		 * @private
    		 * @param a
    		 * @param b
    		 * @throws {ValidationError}
    		 */
    		function concat(a, b) {
    		    const ab = new Uint8Array(a.length + b.length);
    		    ab.set(a);
    		    ab.set(b, a.length);
    		    return ab;
    		}
    		/**
    		 * Computes a checksum from the given input data as specified for the CashAddr
    		 * format: https://github.com/Bitcoin-UAHF/spec/blob/master/cashaddr.md.
    		 *
    		 * @private
    		 * @param data Array of 5-bit integers over which the checksum is to be computed.
    		 */
    		function polymod(data) {
    		    const GENERATOR = [
    		        BigInt('0x98f2bc8e61'),
    		        BigInt('0x79b76d99e2'),
    		        BigInt('0xf33e5fb3c4'),
    		        BigInt('0xae2eabe2a8'),
    		        BigInt('0x1e4f43e470'),
    		    ];
    		    let checksum = 1n; // BigInt for 1
    		    for (let i = 0; i < data.length; i += 1) {
    		        const value = BigInt(data[i]);
    		        const topBits = checksum >> 35n;
    		        checksum = ((checksum & 0x07ffffffffn) << 5n) ^ value;
    		        for (let j = 0; j < GENERATOR.length; ++j) {
    		            if ((topBits >> BigInt(j)) & 1n) {
    		                checksum ^= GENERATOR[j];
    		            }
    		        }
    		    }
    		    return checksum ^ 1n;
    		}
    		/**
    		 * Verify that the payload has not been corrupted by checking that the
    		 * checksum is valid.
    		 *
    		 * @private
    		 * @param prefix Cash address prefix. E.g.: 'ecash'.
    		 * @param payload Array of 5-bit integers containing the address' payload.
    		 */
    		function validChecksum(prefix, payload) {
    		    const prefixData = concat(prefixToUint5Array(prefix), new Uint8Array(1));
    		    const checksumData = concat(prefixData, payload);
    		    return polymod(checksumData) === 0n;
    		}
    		/**
    		 * Returns true if, and only if, the given string contains either uppercase
    		 * or lowercase letters, but not both.
    		 *
    		 * @private
    		 * @param string Input string.
    		 */
    		function hasSingleCase(string) {
    		    return string === string.toLowerCase() || string === string.toUpperCase();
    		}
    		/**
    		 * Returns a uint8array for a given string input
    		 *
    		 * @private
    		 * @param string Input string.
    		 */
    		function stringToUint8Array(string) {
    		    const array = new Uint8Array(string.length / 2);
    		    for (let i = 0; i < string.length; i += 2) {
    		        // Convert each pair of characters to an integer
    		        array[i / 2] = parseInt(string.slice(i, i + 2), 16);
    		    }
    		    return array;
    		}
    		/**
    		 * Returns a uint8array for a given string input
    		 *
    		 * @private
    		 * @param uint8Array Input string.
    		 */
    		function uint8arrayToHexString(uint8Array) {
    		    let hexString = '';
    		    for (let i = 0; i < uint8Array.length; i++) {
    		        let hex = uint8Array[i].toString(16);
    		        // Ensure we have 2 digits for each byte
    		        hex = hex.length === 1 ? '0' + hex : hex;
    		        hexString += hex;
    		    }
    		    return hexString;
    		}
    		/**
    		 * Get type and hash from an outputScript
    		 *
    		 * Supported outputScripts:
    		 *
    		 * P2PKH: 76a914<hash>88ac
    		 * P2SH:  a914<hash>87
    		 *
    		 * Validates for supported outputScript and hash length *
    		 *
    		 * @param outputScript an ecash tx outputScript
    		 * @throws {ValidationError}
    		 */
    		function getTypeAndHashFromOutputScript(outputScript) {
    		    const p2pkhPrefix = '76a914';
    		    const p2pkhSuffix = '88ac';
    		    const p2shPrefix = 'a914';
    		    const p2shSuffix = '87';
    		    let hash, type;
    		    // If outputScript begins with '76a914' and ends with '88ac'
    		    if (outputScript.slice(0, p2pkhPrefix.length) === p2pkhPrefix &&
    		        outputScript.slice(-1 * p2pkhSuffix.length) === p2pkhSuffix) {
    		        // We have type p2pkh
    		        type = 'p2pkh';
    		        // hash is the string in between '76a914' and '88ac'
    		        hash = outputScript.substring(outputScript.indexOf(p2pkhPrefix) + p2pkhPrefix.length, outputScript.lastIndexOf(p2pkhSuffix));
    		        // If outputScript begins with 'a914' and ends with '87'
    		    }
    		    else if (outputScript.slice(0, p2shPrefix.length) === p2shPrefix &&
    		        outputScript.slice(-1 * p2shSuffix.length) === p2shSuffix) {
    		        // We have type p2sh
    		        type = 'p2sh';
    		        // hash is the string in between 'a914' and '87'
    		        hash = outputScript.substring(outputScript.indexOf(p2shPrefix) + p2shPrefix.length, outputScript.lastIndexOf(p2shSuffix));
    		        }
    		    else {
    		        // Throw validation error if outputScript not of these two types
    		        throw new ValidationError('Unsupported outputScript: ' + outputScript);
    		    }
    		    // Throw validation error if hash is of invalid size
    		    // Per spec, valid hash sizes in bytes
    		    const VALID_SIZES = [20, 24, 28, 32, 40, 48, 56, 64];
    		    if (!VALID_SIZES.includes(hash.length / 2)) {
    		        throw new ValidationError('Invalid hash size in outputScript: ' + outputScript);
    		    }
    		    return { type, hash };
    		}
    		const getOutputScriptFromTypeAndHash = (type, hash) => {
    		    validate(type === 'p2pkh' || type === 'p2sh', 'Invalid type: ' + type + '.');
    		    let outputScript;
    		    if (type === 'p2pkh') {
    		        outputScript = `76a914${hash}88ac`;
    		    }
    		    else {
    		        outputScript = `a914${hash}87`;
    		    }
    		    return outputScript;
    		};
    		exports.getOutputScriptFromTypeAndHash = getOutputScriptFromTypeAndHash;
    		/**
    		 * Encodes a given outputScript into an eCash address using the optionally specified prefix.
    		 *
    		 * @static
    		 * @param outputScript an ecash tx outputScript
    		 * @param prefix Cash address prefix. E.g.: 'ecash'.
    		 * @throws {ValidationError}
    		 */
    		function encodeOutputScript(outputScript, prefix = 'ecash') {
    		    // Get type and hash from outputScript
    		    const { type, hash } = getTypeAndHashFromOutputScript(outputScript);
    		    // The encode function validates hash for correct length
    		    return encodeCashAddress(prefix, type, hash);
    		}
    		/**
    		 * Return true for a valid cashaddress
    		 * Prefixless addresses with valid checksum are also valid
    		 *
    		 * @static
    		 * @param testedAddress a string tested for cashaddress validity
    		 * @param optionalPrefix cashaddr prefix
    		 * @throws {ValidationError}
    		 */
    		function isValidCashAddress(cashaddress, optionalPrefix = false) {
    		    try {
    		        const { prefix } = decodeCashAddress(cashaddress);
    		        if (optionalPrefix) {
    		            return prefix === optionalPrefix;
    		        }
    		        return true;
    		    }
    		    catch {
    		        return false;
    		    }
    		}
    		/**
    		 * Return true for a valid cashaddress
    		 * Prefixless addresses with valid checksum are also valid
    		 *
    		 * @static
    		 * @param address a valid p2pkh or p2sh cash address
    		 * @returns the outputScript associated with this address and type
    		 * @throws {ValidationError} if decode fails
    		 */
    		function getOutputScriptFromAddress(address) {
    		    const { type, hash } = decodeCashAddress(address);
    		    let registrationOutputScript;
    		    if (type === 'p2pkh') {
    		        registrationOutputScript = `76a914${hash}88ac`;
    		    }
    		    else {
    		        registrationOutputScript = `a914${hash}87`;
    		    }
    		    return registrationOutputScript;
    		} 
    	} (cashaddr));
    	return cashaddr;
    }

    var cashaddrExports = requireCashaddr();

    // src/addressValidator.js

    document.addEventListener('DOMContentLoaded', () => {
      // Find the eCash address input field by its ID.
      const addressInput = document.getElementById('ecash_address');
      if (!addressInput) return;
      
      // Find or create a span for validation feedback.
      let resultSpan = document.getElementById('ecashAddressValidationResult');
      if (!resultSpan) {
        resultSpan = document.createElement('span');
        resultSpan.id = 'ecashAddressValidationResult';
        addressInput.parentNode.appendChild(resultSpan);
      }
      
      // Select the "Save Changes" button by its name attribute.
      const saveButton = document.querySelector('button[name="paybutton_paywall_save_settings"]');
      
      // Listen to input events to auto-validate as the user types.
      addressInput.addEventListener('input', () => {
        const address = addressInput.value.trim();
        if (address === "") {
          resultSpan.textContent = '❌ Invalid eCash address';
          resultSpan.style.color = 'red';
          if (saveButton) saveButton.disabled = true;
          return;
        }
        
        const valid = cashaddrExports.isValidCashAddress(address);
        if (valid) {
          resultSpan.textContent = '✅ Valid eCash address';
          resultSpan.style.color = 'green';
          if (saveButton) saveButton.disabled = false;
        } else {
          resultSpan.textContent = '❌ Invalid eCash address';
          resultSpan.style.color = 'red';
          if (saveButton) saveButton.disabled = true;
        }
      });
      
      // Run validation immediately on page load in case the field already has a value.
      addressInput.dispatchEvent(new Event('input'));
    });

})();
