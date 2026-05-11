package config

import (
	"encoding/json"
	"io/fs"
	"os"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"
	"sync"

	"easy-local/core/utils/mapper"
	"easy-local/core/utils/slicer"
)

type Config struct {
	root    string
	configs map[string]any
}

var (
	instance *Config
	once     sync.Once
	// DefaultDir 默认配置文件目录
	DefaultDir = "config"
	// 子集分隔符
	DefaultSpe = "."
	// 默认切片零值
	DefaultSliceVal = ""
)

func Instance() *Config {
	once.Do(func() {
		instance = new(Config)
		instance.Init()
	})
	return instance
}

// Init 初始化
func (conf *Config) Init() {
	conf.configs = map[string]any{}

	dir, _ := os.Getwd()
	conf.root = filepath.Join(dir, DefaultDir)
	conf.load(conf.root)
}

// load 加载配置
func (conf *Config) load(dir string) {

	filepath.WalkDir(dir, func(path string, d fs.DirEntry, err error) error {
		if err != nil {
			return err
		}

		// 跳过目录
		if d.IsDir() {
			conf.load(filepath.Join(path, d.Name()))
			return nil
		}

		ext := ".json"

		// 判断 .json 文件（忽略大小写）
		if strings.EqualFold(filepath.Ext(path), ext) {
			// 相对路径【仅go run 时有效】
			repath := path[len(conf.root)+strings.LastIndex(path, conf.root)+1:]
			repath = repath[:len(repath)-len(ext)]

			// 读取配置
			content, err := os.ReadFile(path)
			if err != nil {
				panic(err)
			}

			vals := map[string]any{}
			json.Unmarshal(content, &vals)

			// 拆
			rearr := strings.Split(repath, string(filepath.Separator))
			slicer.Reverse(rearr)

			for _, name := range rearr {
				value := map[string]any{}
				value[name] = vals
				vals = value
			}

			// 合并配置
			conf.configs = mapper.DeepMerge(conf.configs, vals)
		}

		return nil
	})

}

// Get 获取配置数据
func (conf *Config) Get(key string) any {

	// 分割点
	keys := strings.Split(key, DefaultSpe)

	var value any

	value = conf.configs
	if len(keys) <= 0 {
		return value
	}

	ok := true

	for _, key := range keys {
		switch val := value.(type) {
		case map[string]any:
			if value, ok = val[key]; !ok {
				return nil
			}
		case []any:
			idx, _ := strconv.Atoi(key)
			value = val[idx]
		default:
			return nil
		}
	}

	return value
}

// Set 设置配置数据
func (conf *Config) Set(key string, val any) *Config {
	if key == "" {
		return conf
	}

	keys := strings.Split(key, DefaultSpe)
	tmps := append([]string{}, keys...)

	length := len(keys)
	if length <= 0 {
		return conf
	}

	if length == 1 {
		conf.configs[keys[0]] = val
		return conf
	}

	// 反序
	slicer.Reverse(keys)

	// 键列表
	keyLen := len(keys)

	// 遍历
	for idx, keyStr := range keys {
		// 判断如果是数字的话，那就应该是切片
		if ok, _ := regexp.MatchString(`\d+`, keyStr); ok {
			k, _ := strconv.Atoi(keyStr)

			// 计算key位置
			i := keyLen - idx - 1

			// 获取切片列表
			value := conf.Get(strings.Join(tmps[:i], DefaultSpe)).([]any)
			vLen := len(value)

			if vLen > k {
				value[k] = val
			} else {
				// 没有的就添加默认
				for i, j := k-vLen, 0; j < i; j++ {
					value = append(value, DefaultSliceVal)
				}
				value = append(value, val)
			}
			val = value
		} else {
			value := map[string]any{}
			value[keyStr] = val
			val = value
		}
	}

	// 合并配置
	conf.configs = mapper.DeepMerge(conf.configs, val.(map[string]any))

	return conf
}
