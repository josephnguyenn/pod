/**
 * Path Offset Library - Alternative to Inkscape stroke-to-path
 * 
 * This library converts SVG strokes to fills by creating offset/expanded paths.
 * Works entirely in browser - no server dependencies needed!
 * 
 * Usage:
 *   const offset = new PathOffset();
 *   const expandedPath = offset.offsetPath(pathData, strokeWidth);
 */

class PathOffset {
    constructor() {
        this.tolerance = 0.1; // Path simplification tolerance
    }

    /**
     * Convert stroke to fill by creating expanded path
     * This simulates Inkscape's stroke-to-path functionality
     */
    strokeToPath(pathData, strokeWidth, strokeLinejoin = 'round', strokeLinecap = 'round') {
        try {
            // Parse path data
            const segments = this.parsePathData(pathData);
            
            // Create offset path (expanded outward)
            const offsetSegments = this.offsetPath(segments, strokeWidth, strokeLinejoin, strokeLinecap);
            
            // Convert back to path data string
            const expandedPathData = this.segmentsToPathData(offsetSegments);
            
            return expandedPathData;
        } catch (error) {
            console.error('PathOffset: Error converting stroke to path:', error);
            return null;
        }
    }

    /**
     * Parse SVG path data into segments
     */
    parsePathData(pathData) {
        const segments = [];
        const commands = pathData.match(/[MmLlHhVvCcSsQqTtAaZz][^MmLlHhVvCcSsQqTtAaZz]*/g) || [];
        
        let currentX = 0;
        let currentY = 0;
        let startX = 0;
        let startY = 0;

        for (const cmd of commands) {
            const type = cmd[0];
            const coords = cmd.slice(1).trim().split(/[\s,]+/).map(parseFloat).filter(n => !isNaN(n));
            
            const segment = {
                type: type,
                coords: coords,
                x: currentX,
                y: currentY
            };

            // Update current position
            switch (type.toUpperCase()) {
                case 'M':
                    currentX = coords[0];
                    currentY = coords[1];
                    startX = currentX;
                    startY = currentY;
                    break;
                case 'L':
                    currentX = coords[0];
                    currentY = coords[1];
                    break;
                case 'H':
                    currentX = coords[0];
                    break;
                case 'V':
                    currentY = coords[0];
                    break;
                case 'Z':
                case 'z':
                    currentX = startX;
                    currentY = startY;
                    break;
                case 'C':
                    currentX = coords[4];
                    currentY = coords[5];
                    break;
                case 'S':
                    currentX = coords[2];
                    currentY = coords[3];
                    break;
                case 'Q':
                    currentX = coords[2];
                    currentY = coords[3];
                    break;
                case 'T':
                    currentX = coords[0];
                    currentY = coords[1];
                    break;
            }

            segments.push(segment);
        }

        return segments;
    }

    /**
     * Offset path segments outward
     * Simplified version - creates approximate offset
     */
    offsetPath(segments, offset, linejoin, linecap) {
        // For simplicity, we'll use a scaling approach
        // This is not perfect but works for most cases
        
        const offsetSegments = [];
        let centerX = 0;
        let centerY = 0;
        let count = 0;

        // Calculate center point
        for (const seg of segments) {
            if (seg.type.toUpperCase() !== 'Z') {
                centerX += seg.x;
                centerY += seg.y;
                count++;
            }
        }
        
        if (count > 0) {
            centerX /= count;
            centerY /= count;
        }

        // Scale path outward from center
        const scale = 1 + (offset * 2) / 100; // Approximate scaling

        for (const seg of segments) {
            const offsetSeg = { ...seg };
            
            if (seg.type.toUpperCase() !== 'Z') {
                // Offset coordinates
                offsetSeg.x = centerX + (seg.x - centerX) * scale;
                offsetSeg.y = centerY + (seg.y - centerY) * scale;
                
                // Offset coordinate arrays
                if (seg.coords && seg.coords.length > 0) {
                    offsetSeg.coords = seg.coords.map((coord, idx) => {
                        if (idx % 2 === 0) {
                            // X coordinate
                            return centerX + (coord - centerX) * scale;
                        } else {
                            // Y coordinate
                            return centerY + (coord - centerY) * scale;
                        }
                    });
                }
            }
            
            offsetSegments.push(offsetSeg);
        }

        return offsetSegments;
    }

    /**
     * Convert segments back to path data string
     */
    segmentsToPathData(segments) {
        let pathData = '';
        
        for (const seg of segments) {
            pathData += seg.type;
            
            if (seg.coords && seg.coords.length > 0) {
                // Format coordinates
                for (let i = 0; i < seg.coords.length; i += 2) {
                    if (i > 0) pathData += ' ';
                    pathData += seg.coords[i].toFixed(2);
                    if (i + 1 < seg.coords.length) {
                        pathData += ',' + seg.coords[i + 1].toFixed(2);
                    }
                }
            }
        }
        
        return pathData;
    }

    /**
     * Create outline effect using mask/clip-path approach
     * This is more reliable than path offsetting
     */
    createOutlineWithMask(svgElement, pathElement, strokeWidth, patternUrl) {
        const namespace = 'http://www.w3.org/2000/svg';
        
        // Get path bounding box
        const bbox = pathElement.getBBox();
        const padding = strokeWidth * 2;
        
        // Create expanded path group
        const outlineGroup = document.createElementNS(namespace, 'g');
        
        // Create expanded rect filled with pattern
        const expandedRect = document.createElementNS(namespace, 'rect');
        expandedRect.setAttribute('x', bbox.x - padding);
        expandedRect.setAttribute('y', bbox.y - padding);
        expandedRect.setAttribute('width', bbox.width + padding * 2);
        expandedRect.setAttribute('height', bbox.height + padding * 2);
        expandedRect.setAttribute('fill', patternUrl);
        
        // Create mask to cut out center
        const maskId = 'mask-' + Date.now();
        const mask = document.createElementNS(namespace, 'mask');
        mask.setAttribute('id', maskId);
        
        // White background (show all)
        const maskBg = document.createElementNS(namespace, 'rect');
        maskBg.setAttribute('x', bbox.x - padding);
        maskBg.setAttribute('y', bbox.y - padding);
        maskBg.setAttribute('width', bbox.width + padding * 2);
        maskBg.setAttribute('height', bbox.height + padding * 2);
        maskBg.setAttribute('fill', 'white');
        mask.appendChild(maskBg);
        
        // Black path (hide center)
        const maskPath = pathElement.cloneNode(true);
        maskPath.setAttribute('fill', 'black');
        maskPath.setAttribute('stroke', 'none');
        mask.appendChild(maskPath);
        
        // Add mask to defs
        let defs = svgElement.querySelector('defs');
        if (!defs) {
            defs = document.createElementNS(namespace, 'defs');
            svgElement.insertBefore(defs, svgElement.firstChild);
        }
        defs.appendChild(mask);
        
        // Apply mask to expanded rect
        expandedRect.setAttribute('mask', 'url(#' + maskId + ')');
        outlineGroup.appendChild(expandedRect);
        
        return outlineGroup;
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PathOffset;
}
