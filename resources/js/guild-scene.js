// Original monochrome pixel scenery. Every shape is drawn at the logical
// resolution; CSS may enlarge the canvas without interpolating the pixels.
export function mountGuildScene(canvas) {
    if (!(canvas instanceof HTMLCanvasElement)) return () => {};

    const context = canvas.getContext('2d', { alpha: false });
    if (!context) return () => {};

    const width = 560;
    const height = 260;
    canvas.width = width;
    canvas.height = height;
    context.imageSmoothingEnabled = false;

    const colors = { black: '#080808', night: '#121212', stone: '#393939', middle: '#696969', light: '#b4b4b4', white: '#eeeeea' };
    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const isPaused = () => canvas.dataset.paused === 'true';
    let reduced = motionQuery.matches;
    let visible = true;
    let disposed = false;
    let frameRequest = 0;
    let elapsed = 0;
    let lastFrame = 0;
    let lastPaint = 0;
    const pointer = { x: 0, y: 0, currentX: 0, currentY: 0 };

    let seed = 71;
    const random = () => {
        seed = (seed * 16807) % 2147483647;
        return (seed - 1) / 2147483646;
    };
    const stars = Array.from({ length: 46 }, () => ({ x: Math.floor(random() * width), y: Math.floor(random() * 150), phase: random() * 6, bright: random() > .8 }));
    const dust = Array.from({ length: 27 }, () => ({ x: Math.floor(random() * width), y: Math.floor(random() * height), speed: .4 + random(), phase: random() * 7 }));
    const paving = Array.from({ length: 90 }, () => ({ x: Math.floor(random() * width), y: 203 + Math.floor(random() * 57), length: 1 + Math.floor(random() * 4) }));
    const stoneMarks = Array.from({ length: 60 }, () => ({ x: 35 + Math.floor(random() * 169), y: 105 + Math.floor(random() * 86), length: 2 + Math.floor(random() * 7) }));

    function rect(x, y, w, h, color) {
        context.fillStyle = color;
        context.fillRect(Math.round(x), Math.round(y), w, h);
    }

    function line(x0, y0, x1, y1, color) {
        x0 = Math.round(x0); y0 = Math.round(y0);
        x1 = Math.round(x1); y1 = Math.round(y1);
        const dx = Math.abs(x1 - x0);
        const dy = -Math.abs(y1 - y0);
        const sx = x0 < x1 ? 1 : -1;
        const sy = y0 < y1 ? 1 : -1;
        let error = dx + dy;
        context.fillStyle = color;
        while (true) {
            context.fillRect(x0, y0, 1, 1);
            if (x0 === x1 && y0 === y1) break;
            const twiceError = error * 2;
            if (twiceError >= dy) { error += dy; x0 += sx; }
            if (twiceError <= dx) { error += dx; y0 += sy; }
        }
    }

    function outline(x, y, w, h, color) {
        rect(x, y, w, 1, color); rect(x, y + h - 1, w, 1, color);
        rect(x, y, 1, h, color); rect(x + w - 1, y, 1, h, color);
    }

    function polygon(points, fill, stroke) {
        context.beginPath();
        context.moveTo(points[0][0], points[0][1]);
        points.slice(1).forEach(([x, y]) => context.lineTo(x, y));
        context.closePath();
        context.fillStyle = fill;
        context.fill();
        if (stroke) {
            points.forEach(([x, y], i) => {
                const next = points[(i + 1) % points.length];
                line(x, y, next[0], next[1], stroke);
            });
        }
    }

    function arch(x, y, w, h, color, thickness = 2) {
        const top = Math.round(w / 3);
        rect(x, y + top, thickness, h - top, color);
        rect(x + w - thickness, y + top, thickness, h - top, color);
        const points = [[x, y + top], [x + 5, y + 7], [x + top, y], [x + w - top, y], [x + w - 5, y + 7], [x + w - 1, y + top]];
        points.slice(0, -1).forEach(([px, py], i) => {
            line(px, py, points[i + 1][0], points[i + 1][1], color);
            if (thickness > 1) line(px, py + 1, points[i + 1][0], points[i + 1][1] + 1, color);
        });
    }

    function torch(x, y, time, offset = 0) {
        const frame = Math.floor(time * 7 + offset) % 4;
        rect(x - 3, y + 5, 7, 2, colors.stone);
        rect(x, y + 5, 1, 10, colors.middle);
        rect(x - 2, y + 1, 5, 4, colors.white);
        rect(x - 1 + (frame === 1 ? 1 : 0), y - 3, 2, 5, colors.light);
        rect(x + (frame === 2 ? -2 : 1), y - 6 + (frame % 2), 1, 4, colors.white);
        rect(x - 5, y + 1, 1, 1, colors.stone);
        rect(x + 5, y - 2, 1, 1, colors.stone);
        if (frame === 3) rect(x + 2, y - 10, 1, 1, colors.light);
    }

    function banner(x, y, time, variant = 0) {
        rect(x, y - 9, 1, 35, colors.light);
        rect(x - 1, y - 11, 3, 3, colors.white);
        const wave = Math.sin(time * 2.1 + variant);
        for (let column = 0; column < 20; column++) {
            const shift = Math.round(Math.sin(column * .2 + time * 2.1 + variant) * column * .09);
            const tail = column > 13 ? Math.round((column - 13) / 2) : 0;
            rect(x + 1 + column, y + shift, 1, 16 - tail, colors.white);
            if (column > 5 && column < 13) rect(x + 1 + column, y + 5 + shift, 1, 5, colors.black);
        }
        rect(x + 9, y + 3 + Math.round(wave), 2, 11, colors.black);
    }

    function mountains(time) {
        const shift = Math.round(pointer.currentX);
        context.save();
        context.translate(shift, 0);
        polygon([[0, 172], [0, 154], [27, 136], [41, 147], [74, 110], [98, 132], [139, 98], [166, 127], [185, 118], [214, 148], [244, 128], [270, 143], [304, 106], [325, 130], [358, 104], [383, 132], [406, 121], [437, 145], [470, 112], [499, 139], [530, 121], [560, 144], [560, 200]], colors.night);
        line(238, 141, 270, 158, '#242424');
        line(274, 145, 304, 108, '#242424');
        line(304, 108, 322, 136, '#242424');
        line(334, 133, 359, 107, '#242424');
        rect(260, 173, 89, 1, colors.stone);
        stars.forEach((star) => {
            const bright = reduced || Math.sin(time * .55 + star.phase) > .45;
            rect(star.x, star.y, 1, 1, star.bright && bright ? colors.light : colors.stone);
            if (star.bright && bright) { rect(star.x - 2, star.y, 5, 1, colors.stone); rect(star.x, star.y - 2, 1, 5, colors.stone); }
        });
        // Broken moon, deliberately built from hard-edged steps.
        rect(285, 29, 14, 2, colors.light); rect(281, 31, 22, 3, colors.light);
        rect(278, 34, 28, 14, colors.light); rect(281, 48, 22, 3, colors.light); rect(285, 51, 14, 2, colors.light);
        rect(290, 28, 13, 20, colors.black); rect(299, 34, 9, 13, colors.black);
        context.restore();
    }

    function castle(time) {
        context.save();
        context.translate(Math.round(pointer.currentX * 2), Math.round(pointer.currentY));
        // The silhouette consists of three irregular towers and an open gate.
        rect(30, 99, 23, 103, colors.black); outline(30, 99, 23, 103, colors.stone);
        rect(34, 91, 5, 10, colors.middle); rect(45, 94, 5, 7, colors.middle);
        polygon([[52, 86], [60, 86], [60, 64], [68, 64], [68, 73], [76, 73], [76, 64], [84, 64], [84, 73], [92, 73], [92, 64], [99, 64], [99, 86], [103, 86], [103, 202], [52, 202]], colors.black, colors.light);
        rect(58, 90, 40, 3, colors.stone); rect(58, 95, 40, 1, colors.middle);
        rect(105, 121, 81, 81, colors.black); outline(105, 121, 81, 81, colors.middle);
        for (let x = 106; x < 180; x += 14) { rect(x, 114, 7, 9, colors.middle); rect(x + 1, 114, 5, 7, colors.black); }
        polygon([[181, 102], [185, 102], [185, 82], [193, 82], [193, 90], [201, 90], [201, 82], [208, 82], [208, 102], [213, 102], [213, 202], [181, 202]], colors.black, colors.middle);
        rect(186, 106, 22, 2, colors.stone);
        // Thin mortar breaks and chipped stone avoid a pristine vector look.
        stoneMarks.forEach((mark) => rect(mark.x, mark.y, mark.length, 1, '#252525'));
        rect(72, 105, 8, 20, colors.stone); rect(74, 106, 4, 17, colors.white);
        rect(73, 113, 6, 2, colors.black);
        rect(191, 122, 6, 15, colors.stone); rect(193, 123, 2, 12, colors.light);
        rect(62, 150, 6, 12, colors.middle); rect(64, 152, 2, 8, colors.white);
        arch(130, 151, 31, 51, colors.light);
        arch(134, 155, 23, 47, colors.stone, 1);
        rect(139, 177, 1, 24, colors.middle); rect(145, 177, 1, 24, colors.middle); rect(151, 177, 1, 24, colors.middle);
        rect(125, 202, 42, 3, colors.middle); rect(120, 206, 52, 2, colors.stone); rect(114, 210, 63, 1, colors.stone);
        line(86, 137, 80, 144, colors.stone); line(80, 144, 83, 152, colors.stone); line(83, 152, 77, 160, colors.stone);
        banner(79, 44, time);
        banner(198, 64, time, 1.7);
        torch(118, 175, time);
        torch(171, 175, time, 2);
        rect(19, 202, 200, 1, colors.stone);
        context.restore();
    }

    function portal(time) {
        context.save();
        context.translate(Math.round(pointer.currentX * 1.5), 0);
        // Heavy, separate stone blocks around the luminous entrance.
        rect(411, 83, 18, 118, colors.night); rect(484, 83, 18, 118, colors.night);
        for (let y = 86; y < 198; y += 15) {
            outline(411, y, 18, 14, colors.stone); outline(484, y, 18, 14, colors.stone);
            rect(413, y + 2, 12, 1, colors.middle); rect(486, y + 2, 12, 1, colors.middle);
        }
        polygon([[417, 86], [417, 73], [431, 57], [444, 48], [468, 48], [483, 57], [496, 73], [496, 86], [484, 86], [478, 73], [468, 65], [444, 65], [434, 73], [429, 86]], colors.night, colors.middle);
        line(444, 49, 445, 63, colors.stone); line(468, 49, 467, 63, colors.stone);
        line(431, 58, 439, 69, colors.stone); line(483, 58, 474, 69, colors.stone);
        arch(432, 68, 49, 135, colors.white);
        arch(436, 73, 41, 130, colors.stone, 1);
        const pulse = .5 + Math.sin(time * 1.6) * .5;
        for (let y = 97; y < 194; y += 5) {
            const phase = (Math.floor(time * 6) + y) % 9;
            rect(440 + (phase % 3), y, 2, 1, pulse > .5 ? colors.stone : colors.night);
            rect(472 - (phase % 3), y + 2, 1, 1, colors.middle);
        }
        // A small suspended sigil drifts inside the gate.
        const bob = reduced ? 0 : Math.round(Math.sin(time * 1.3) * 3);
        const sigilY = 126 + bob;
        line(456, sigilY - 10, 464, sigilY, colors.light); line(464, sigilY, 456, sigilY + 10, colors.light);
        line(456, sigilY + 10, 448, sigilY, colors.light); line(448, sigilY, 456, sigilY - 10, colors.light);
        rect(455, sigilY - 3, 3, 7, colors.white);
        rect(408, 202, 97, 2, colors.middle); rect(403, 207, 107, 1, colors.stone);
        for (let i = 0; i < 11; i++) {
            const y = 194 - ((time * 12 + i * 19) % 119);
            const x = 439 + ((i * 13) % 34) + Math.round(Math.sin(time + i) * 2);
            rect(x, y, 1, 2, i % 3 ? colors.middle : colors.light);
        }
        torch(396, 161, time, 1);
        torch(516, 161, time, 3);
        context.restore();
    }

    function person(x, y, time, variant, walking = true) {
        const frame = Math.floor(time * 5 + variant) % 4;
        const bob = walking && frame % 2 ? -1 : 0;
        x = Math.round(x); y = Math.round(y) + bob;
        rect(x - 4, y + 14 - bob, 11, 1, colors.stone);
        rect(x, y, 5, 2, colors.light); rect(x - 1, y + 2, 7, 4, colors.white);
        rect(x + (variant % 2 ? 3 : 0), y + 3, 2, 2, colors.black);
        rect(x, y + 6, 5, 6, colors.middle); rect(x - 2, y + 7, 2, 5, colors.light);
        rect(x + 5, y + 7, 2, 5, colors.light); rect(x + 1, y + 7, 3, 2, colors.white);
        const stride = walking ? (frame < 2 ? 1 : -1) : 0;
        rect(x + stride, y + 12, 2, 3, colors.light); rect(x + 3 - stride, y + 12, 2, 3, colors.light);
        if (variant % 2) { rect(x + 8, y + 4, 1, 12, colors.light); rect(x + 7, y + 4, 3, 2, colors.white); }
        else { rect(x - 4, y + 7, 3, 5, colors.white); rect(x + 8, y + 7, 1, 7, colors.light); }
    }

    function courtyard(time) {
        line(225, 203, 392, 203, colors.stone);
        line(220, 205, 384, 205, colors.night);
        paving.forEach((stone, i) => rect(stone.x, stone.y, stone.length, 1, i % 7 === 0 ? colors.middle : colors.stone));
        for (let i = 0; i < 5; i++) {
            const y = 216 + i * 10;
            const center = 255 + i * 7;
            line(center - 24 - i * 5, y, center + 22 + i * 7, y, colors.stone);
            rect(center + (i % 2 ? 9 : -13), y - 3, 1, 3, colors.stone);
        }
        const patrol = reduced ? .42 : .5 + Math.sin(time * .17) * .5;
        person(233 + patrol * 98, 199, time, 0);
        person(84 + (reduced ? 10 : Math.sin(time * .12) * 21), 220, time, 1);
        person(366, 207, time, 2, false);
        // Low wall, abandoned crate, grass and hanging chains frame the scene.
        outline(236, 189, 14, 11, colors.middle); line(237, 190, 248, 198, colors.stone); line(248, 190, 237, 198, colors.stone);
        rect(349, 187, 28, 2, colors.middle); rect(352, 190, 3, 11, colors.stone); rect(372, 190, 3, 11, colors.stone);
        for (const x of [16, 21, 24, 201, 205, 380, 385, 530, 535]) {
            line(x, 219, x - 3, 212 - x % 5, colors.stone);
            line(x, 219, x + 2, 211 + x % 3, colors.middle);
        }
        for (let i = 0; i < 17; i++) {
            const x = 180 + i * 10;
            const drift = Math.round(Math.sin(time * .18 + i) * 6);
            rect(x + drift, 176 + i % 3 * 4, i % 2 ? 10 : 4, 1, '#202020');
        }
    }

    function draw(time) {
        context.fillStyle = colors.black;
        context.fillRect(0, 0, width, height);
        mountains(time);
        castle(time);
        portal(time);
        courtyard(time);
        dust.forEach((particle, i) => {
            const y = (particle.y - time * particle.speed * 3 + height * 100) % height;
            const x = particle.x + Math.round(Math.sin(time * .25 + particle.phase) * 6);
            rect(x, y, 1, 1, i % 6 ? colors.stone : colors.light);
        });
        // Sparse, static foreground dither keeps the silhouette legible.
        for (let x = 0; x < width; x += 7) rect(x, 253 + (x % 3), 2, 1, colors.stone);
    }

    function animate(timestamp) {
        frameRequest = 0;
        if (disposed || reduced || isPaused() || !visible || document.hidden) return;
        if (!lastFrame) lastFrame = timestamp;
        elapsed += Math.min(timestamp - lastFrame, 100);
        lastFrame = timestamp;
        if (timestamp - lastPaint >= 1000 / 24) {
            pointer.currentX += (pointer.x - pointer.currentX) * .08;
            pointer.currentY += (pointer.y - pointer.currentY) * .08;
            draw(elapsed / 1000);
            lastPaint = timestamp;
        }
        frameRequest = requestAnimationFrame(animate);
    }

    function synchronize() {
        if (frameRequest) cancelAnimationFrame(frameRequest);
        frameRequest = 0;
        lastFrame = 0;
        if (disposed) return;
        if (reduced || isPaused()) {
            pointer.currentX = 0;
            pointer.currentY = 0;
            draw(0);
        } else if (visible && !document.hidden) {
            frameRequest = requestAnimationFrame(animate);
        }
    }

    function onPointer(event) {
        if (reduced || isPaused()) return;
        const bounds = canvas.getBoundingClientRect();
        pointer.x = (event.clientX - bounds.left) / bounds.width * 2 - 1;
        pointer.y = (event.clientY - bounds.top) / bounds.height * 2 - 1;
    }
    function onLeave() { pointer.x = 0; pointer.y = 0; }
    function onMotionChange(event) { reduced = event.matches; synchronize(); }

    const observer = typeof IntersectionObserver === 'undefined' ? null : new IntersectionObserver((entries) => {
        visible = entries[0]?.isIntersecting ?? true;
        synchronize();
    }, { rootMargin: '50px' });
    observer?.observe(canvas);
    const pauseObserver = new MutationObserver(synchronize);
    pauseObserver.observe(canvas, { attributes: true, attributeFilter: ['data-paused'] });
    canvas.addEventListener('pointermove', onPointer, { passive: true });
    canvas.addEventListener('pointerleave', onLeave, { passive: true });
    motionQuery.addEventListener('change', onMotionChange);
    document.addEventListener('visibilitychange', synchronize);
    draw(0);
    synchronize();

    return () => {
        disposed = true;
        if (frameRequest) cancelAnimationFrame(frameRequest);
        observer?.disconnect();
        pauseObserver.disconnect();
        canvas.removeEventListener('pointermove', onPointer);
        canvas.removeEventListener('pointerleave', onLeave);
        motionQuery.removeEventListener('change', onMotionChange);
        document.removeEventListener('visibilitychange', synchronize);
    };
}
